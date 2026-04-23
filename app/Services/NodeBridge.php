<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NodeBridge
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.wa_gateway.url', 'http://localhost:3001');
    }

    /**
     * Memulai sesi WhatsApp untuk device tertentu
     */
    public function startSession(string $deviceUuid)
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/sessions/start", [
                'device_uuid' => $deviceUuid
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("WA Gateway Start Session Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Menghentikan sesi WhatsApp
     */
    public function stopSession(string $deviceUuid)
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/sessions/stop", [
                'device_uuid' => $deviceUuid
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("WA Gateway Stop Session Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Mengirim pesan teks
     */
    public function sendMessage(string $deviceUuid, string $phone, string $text)
    {
        try {
            $jid = $this->formatJid($phone);

            // Timeout lebih lama karena ada anti-ban delay di Node
            $response = Http::timeout(30)->post("{$this->baseUrl}/messages/send", [
                'device_uuid' => $deviceUuid,
                'jid' => $jid,
                'text' => $text
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("WA Gateway Send Message Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Mendapatkan status sesi
     */
    public function getStatus(string $deviceUuid)
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/sessions/{$deviceUuid}");
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Mendapatkan daftar grup WhatsApp
     */
    public function fetchGroups(string $deviceUuid)
    {
        try {
            $response = Http::timeout(20)->get("{$this->baseUrl}/sessions/{$deviceUuid}/groups");
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Health check - cek apakah Node service hidup
     */
    public function healthCheck()
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Helper untuk format nomor ke JID WhatsApp
     */
    protected function formatJid(string $phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        return $phone . '@s.whatsapp.net';
    }
}
