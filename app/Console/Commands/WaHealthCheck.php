<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaHealthCheck extends Command
{
    /**
     * Nama dan signature command
     * Jalankan: php artisan wa:health-check
     */
    protected $signature = 'wa:health-check';

    /**
     * Deskripsi command
     */
    protected $description = 'Cek status Node.js WhatsApp service dan sinkronkan status device di database';

    public function handle()
    {
        $gatewayUrl = config('services.wa_gateway.url', 'http://localhost:3001');

        // ---- STEP 1: Cek apakah Node service hidup ----
        try {
            $response = Http::timeout(5)->get("{$gatewayUrl}/health");

            if (!$response->ok()) {
                $this->error("❌ Node service merespons tapi tidak OK (HTTP {$response->status()})");
                $this->markAllDevicesOffline();
                return;
            }

            $health = $response->json();
            $this->info("✅ Node service aktif | Uptime: {$health['uptime']}s | Sessions: {$health['activeSessions']}");

        } catch (\Exception $e) {
            $this->error("❌ Node service tidak bisa dihubungi: {$e->getMessage()}");
            Log::warning("WA Health Check: Node service unreachable - {$e->getMessage()}");

            // Node mati → semua device harusnya offline
            $this->markAllDevicesOffline();
            return;
        }

        // ---- STEP 2: Sinkronkan status setiap device ----
        $devices = Device::where('status', 'connected')->get();
        $activeSessions = collect($health['sessions'] ?? []);

        foreach ($devices as $device) {
            $session = $activeSessions->firstWhere('uuid', $device->uuid);

            if (!$session || !$session['connected']) {
                // Device di DB masih "connected" tapi Node bilang tidak aktif
                $device->update(['status' => 'disconnected']);
                $this->warn("⚠️  {$device->name} ({$device->uuid}) → status diupdate ke disconnected");
                Log::info("WA Health Check: Device {$device->name} marked disconnected (not in active sessions)");
            } else {
                $this->info("   ✓ {$device->name} → aktif (phone: {$session['phone']})");
            }
        }

        // ---- STEP 3: Cek device yang di Node aktif tapi di DB disconnected ----
        foreach ($activeSessions as $session) {
            if ($session['connected']) {
                $device = Device::where('uuid', $session['uuid'])
                    ->where('status', '!=', 'connected')
                    ->first();

                if ($device) {
                    $device->update([
                        'status' => 'connected',
                        'phone' => $session['phone']
                    ]);
                    $this->info("   🔄 {$device->name} → status dipulihkan ke connected");
                    Log::info("WA Health Check: Device {$device->name} restored to connected");
                }
            }
        }

        $this->info("\n✅ Health check selesai.");
    }

    /**
     * Tandai semua device sebagai offline
     * Dipanggil ketika Node service tidak bisa dihubungi
     */
    private function markAllDevicesOffline()
    {
        $affected = Device::where('status', 'connected')
            ->update(['status' => 'disconnected']);

        if ($affected > 0) {
            $this->warn("⚠️  {$affected} device ditandai disconnected karena Node service mati.");
            Log::warning("WA Health Check: {$affected} devices marked disconnected (Node service down)");
        }
    }
}
