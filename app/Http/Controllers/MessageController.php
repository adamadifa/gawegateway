<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Tampilkan riwayat pesan
     */
    public function index()
    {
        $messages = Message::with('device')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('messages.index', compact('messages'));
    }

    /**
     * Simpan pesan masuk dari Node.js (API)
     */
    public function storeInbound(Request $request)
    {
        $device = Device::where('uuid', $request->uuid)->firstOrFail();

        Message::create([
            'device_id' => $device->id,
            'remote_jid' => $request->from,
            'push_name' => $request->push_name, // Simpan nama profil WA
            'direction' => 'inbound',
            'type' => 'text',
            'content' => $request->content,
            'status' => 'delivered',
            'sent_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
