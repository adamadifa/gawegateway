<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\NodeBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class DeviceController extends Controller
{
    protected $node;

    public function __construct(NodeBridge $node)
    {
        $this->node = $node;
    }

    /**
     * Update QR Code (callback dari Node service)
     */
    public function updateQr(Request $request) {
        $request->validate([
            'uuid' => 'required',
            'qr' => 'required',
        ]);

        // Simpan QR di cache selama 2 menit
        Cache::put("qr_code_{$request->uuid}", $request->qr, 120);

        return response()->json(['success' => true]);
    }

    /**
     * Tampilkan list perangkat
     */
    public function createMessage() {
        $devices = Device::where('status', 'connected')->get();
        return view('messages.create', compact('devices'));
    }

    public function sendMessage(Request $request) {
        $request->validate([
            'device_uuid' => 'required',
            'phone' => 'required',
            'message' => 'required',
        ]);

        $device = Device::where('uuid', $request->device_uuid)->firstOrFail();

        $result = $this->node->sendMessage($request->device_uuid, $request->phone, $request->message);

        if (isset($result['success'])) {
            // Log the outbound message
            \App\Models\Message::create([
                'device_id' => $device->id,
                'remote_jid' => $request->phone . '@s.whatsapp.net',
                'direction' => 'outbound',
                'type' => 'text',
                'content' => $request->message,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return back()->with('success', 'Message sent successfully!');
        }

        return back()->with('error', 'Failed to send message: ' . ($result['error'] ?? 'Unknown error'));
    }

    public function index()
    {
        $devices = Device::where('user_id', auth()->id() ?? 1)->get(); // Sementara user 1 jika belum auth
        return view('devices.index', compact('devices'));
    }

    /**
     * Simpan perangkat baru dan inisialisasi sesi
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $device = Device::create([
            'user_id' => auth()->id() ?? 1,
            'name' => $request->name,
            'uuid' => Str::uuid(),
            'status' => 'disconnected',
        ]);

        // Beritahu Node.js untuk mulai menyiapkan sesi
        $this->node->startSession($device->uuid);

        return redirect()->route('devices.scan', $device->uuid);
    }

    /**
     * Halaman untuk scan QR
     */
    public function updateStatus(Request $request) {
        $device = Device::where('uuid', $request->uuid)->firstOrFail();
        $device->update([
            'status' => $request->status,
            'phone' => $request->phone,
        ]);
        return response()->json(['success' => true]);
    }

    public function scan($uuid)
    {
        $device = Device::where('uuid', $uuid)->firstOrFail();
        return view('devices.scan', compact('device'));
    }

    /**
     * Hapus perangkat
     */
    public function destroy($uuid)
    {
        $device = Device::where('uuid', $uuid)->firstOrFail();

        // Stop session di Node (best effort)
        $this->node->stopSession($device->uuid);

        // Hapus device (cascading delete akan menghapus pesan terkait)
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Gateway deleted successfully!');
    }
}
