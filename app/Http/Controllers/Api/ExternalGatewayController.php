<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\NodeBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ExternalGatewayController extends Controller
{
    protected NodeBridge $node;

    public function __construct(NodeBridge $node)
    {
        $this->node = $node;
    }

    /**
     * Middleware-like check for API Key
     */
    protected function validateApiKey(Request $request)
    {
        $apiKey = $request->input('api_key');
        if (!$apiKey || $apiKey !== env('WA_API_KEY', 'gawe_key_123')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid API Key'
            ], 401);
        }
        return null;
    }

    /**
     * POST /create-device
     * Parameter: api_key, sender, urlwebhook
     */
    public function createDevice(Request $request)
    {
        if ($error = $this->validateApiKey($request)) return $error;

        $sender = $request->input('sender');
        if (!$sender) {
            return response()->json(['success' => false, 'message' => 'Sender is required'], 400);
        }

        // Cari atau buat device di gawegateway
        $device = Device::where('phone', $sender)->first();
        if (!$device) {
            // Assign ke user pertama (admin) karena ini via external API
            $user = \App\Models\User::first();
            $device = Device::create([
                'user_id' => $user->id,
                'uuid' => (string) Str::uuid(),
                'name' => 'Device ' . $sender,
                'phone' => $sender,
                'status' => 'disconnected'
            ]);
        }

        // Jalankan session di Node
        $this->node->startSession($device->uuid);

        return response()->json([
            'success' => true,
            'message' => 'Device created/initialized',
            'data' => [
                'id' => $device->id,
                'uuid' => $device->uuid,
                'number' => $device->phone,
                'status' => $device->status == 'connected' ? 1 : 0
            ]
        ]);
    }

    /**
     * POST /generate-qr
     * Parameter: api_key, device, force
     */
    public function generateQR(Request $request)
    {
        if ($error = $this->validateApiKey($request)) return $error;

        $number = $request->input('device');
        $device = Device::where('phone', $number)->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found'], 404);
        }

        $status = $this->node->getStatus($device->uuid);
        $isConnected = ($status['connected'] ?? false);

        if ($isConnected) {
            return response()->json([
                'success' => true,
                'status' => 'connected',
                'message' => 'Device sudah terhubung',
                'msg' => 'Device already connected!'
            ]);
        }

        // Cek apakah sudah ada QR di cache
        $qrCode = Cache::get("qr_code_{$device->uuid}");

        if ($qrCode) {
            return response()->json([
                'success' => true,
                'message' => 'QR Code found',
                'qrcode' => $qrCode,
                'status' => 'processing'
            ]);
        }

        // Pastikan session jalan
        $this->node->startSession($device->uuid);

        return response()->json([
            'success' => true,
            'message' => 'QR Code generation initialized',
            'status' => 'processing',
            'uuid' => $device->uuid
        ]);
    }

    /**
     * POST /info-device
     * Parameter: api_key, number
     */
    public function infoDevice(Request $request)
    {
        if ($error = $this->validateApiKey($request)) return $error;

        $number = $request->input('number');
        $device = Device::where('phone', $number)->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found'], 404);
        }

        $status = $this->node->getStatus($device->uuid);
        $isConnected = ($status['connected'] ?? false);

        // Format response agar sesuai yang diharapkan presensigpsv2
        // Kita keluarkan 'info' ke top level (di bawah success) agar tidak double-nesting
        return response()->json([
            'success' => true,
            'info' => [
                [
                    'status' => $isConnected ? 'Connected' : 'Disconnect',
                    'body' => $status['phone'] ?? $device->phone,
                ]
            ]
        ]);
    }

    /**
     * POST /send-message
     * Parameter: api_key, sender, number, message
     */
    public function sendMessage(Request $request)
    {
        if ($error = $this->validateApiKey($request)) return $error;

        $sender = $request->input('sender');
        $recipient = $request->input('number');
        $message = $request->input('message');

        $device = Device::where('phone', $sender)->first();
        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Sender device not found'], 404);
        }

        $result = $this->node->sendMessage($device->uuid, $recipient, $message);

        if (isset($result['success']) && $result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim',
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim pesan: ' . ($result['error'] ?? 'Unknown error')
        ], 500);
    }

    /**
     * POST /logout-device
     * Parameter: api_key, sender
     */
    public function logoutDevice(Request $request)
    {
        if ($error = $this->validateApiKey($request)) return $error;

        $sender = $request->input('sender');
        $device = Device::where('phone', $sender)->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found'], 404);
        }

        $result = $this->node->stopSession($device->uuid);

        return response()->json([
            'success' => true,
            'message' => 'Device logged out successfully',
            'data' => $result
        ]);
    }

    /**
     * POST /fetch-contact-group
     * Parameter: api_key, number
     */
    public function fetchGroups(Request $request)
    {
        if ($error = $this->validateApiKey($request)) return $error;

        $number = $request->input('number');
        $device = Device::where('phone', $number)->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found'], 404);
        }

        $result = $this->node->fetchGroups($device->uuid);

        if (isset($result['success']) && $result['success']) {
            return response()->json([
                'success' => true,
                'status' => true, // Tambahkan ini agar presensigpsv2 mengenali sukses
                'msg' => 'Groups retrieved successfully',
                'data' => [
                    'groups' => $result['groups'],
                    'total_groups' => count($result['groups']),
                    'device_number' => $device->phone
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => 'Failed to fetch groups: ' . ($result['message'] ?? 'Unknown error')
        ], 500);
    }
}
