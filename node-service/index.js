require('dotenv').config();

const cors = require('cors');
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore
} = require('@whiskeysockets/baileys');
const { Boom } = require('@hapi/boom');
const P = require('pino');
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const qrcode = require('qrcode');
const fs = require('fs');
const path = require('path');

const app = express();
const server = http.createServer(app);

// ============================================================
// KONFIGURASI - Semua URL bisa diatur lewat .env
// ============================================================
const PORT = process.env.PORT || 3001;
const LARAVEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';
const CORS_ORIGIN = process.env.CORS_ORIGIN || '*';
const MAX_RETRIES = parseInt(process.env.MAX_RETRIES || '5');
const MAX_QR_RETRIES = parseInt(process.env.MAX_QR_RETRIES || '3');

const io = new Server(server, {
    cors: { origin: CORS_ORIGIN }
});

app.use(express.json());
app.use(cors({ origin: CORS_ORIGIN }));

const sessions = new Map();
const retryCounters = new Map();    // Track retry count per device
const qrRetryCounters = new Map();  // Track QR timeout retries per device
const logger = P({ level: process.env.LOG_LEVEL || 'warn' });
const startTime = Date.now();

// Pastikan folder sessions dan logs ada
const sessionsDir = path.join(__dirname, 'sessions');
if (!fs.existsSync(sessionsDir)) {
    fs.mkdirSync(sessionsDir);
}

const logsDir = path.join(__dirname, 'logs');
if (!fs.existsSync(logsDir)) {
    fs.mkdirSync(logsDir);
}

// ============================================================
// HELPER: Exponential backoff delay  
// Delay naik bertahap: 1s → 2s → 4s → 8s → ... max 60s
// ============================================================
function getBackoffDelay(retryCount) {
    const baseDelay = 1000;
    const maxDelay = 60000;
    return Math.min(baseDelay * Math.pow(2, retryCount), maxDelay);
}

// ============================================================
// HELPER: Notify Laravel tentang perubahan status device
// ============================================================
async function notifyLaravel(endpoint, data) {
    try {
        const url = `${LARAVEL_URL}/api/${endpoint}`;
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error(`[NotifyLaravel] Error notifying ${endpoint}: HTTP ${response.status} - ${errorText.substring(0, 200)}`);
            return false;
        }
        
        return true;
    } catch (err) {
        console.error(`[NotifyLaravel] Failed to reach ${LARAVEL_URL}/api/${endpoint}: ${err.message}`);
        return false;
    }
}

// ============================================================
// CORE: Inisialisasi koneksi WhatsApp untuk device tertentu
// ============================================================
async function connectToWhatsApp(deviceUuid) {
    const sessionPath = path.join(sessionsDir, deviceUuid);
    const { state, saveCreds } = await useMultiFileAuthState(sessionPath);
    const { version } = await fetchLatestBaileysVersion();

    const sock = makeWASocket({
        version,
        logger,
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, logger),
        },
        browser: ["GaweGateway", "Chrome", "1.0.0"],
        // Keepalive bawaan Baileys - otomatis ping ke server WA
        keepAliveIntervalMs: 30000,
    });

    sessions.set(deviceUuid, sock);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        // ---- QR Code diterima ----
        if (qr) {
            console.log(`[${deviceUuid}] QR Code diterima, silakan scan...`);
            const qrData = await qrcode.toDataURL(qr);
            io.emit(`qr-${deviceUuid}`, qrData);
            
            // Notify Laravel tentang QR baru (untuk polling compatibility)
            await notifyLaravel('update-qr', {
                uuid: deviceUuid,
                qr: qrData
            });
        }

        // ---- Koneksi ditutup ----
        if (connection === 'close') {
            const statusCode = (lastDisconnect?.error instanceof Boom)
                ? lastDisconnect.error.output.statusCode
                : null;

            const isLoggedOut = statusCode === DisconnectReason.loggedOut;
            const isQrTimeout = statusCode === 428; // QR code expired/timeout

            // CASE 1: User logout dari HP → hapus session, jangan reconnect
            if (isLoggedOut) {
                console.log(`[${deviceUuid}] Logged out oleh user. Session dihapus.`);
                sessions.delete(deviceUuid);
                retryCounters.delete(deviceUuid);
                qrRetryCounters.delete(deviceUuid);
                fs.rmSync(sessionPath, { recursive: true, force: true });

                // Beritahu Laravel: device disconnected
                await notifyLaravel('update-status', {
                    uuid: deviceUuid,
                    status: 'disconnected',
                    phone: null
                });
                io.emit(`status-${deviceUuid}`, 'disconnected');
                return;
            }

            // CASE 2: QR timeout (tidak ada yang scan) → batasi retry
            if (isQrTimeout) {
                const qrRetries = qrRetryCounters.get(deviceUuid) || 0;
                if (qrRetries >= MAX_QR_RETRIES) {
                    console.log(`[${deviceUuid}] QR timeout ${MAX_QR_RETRIES}x. Berhenti mencoba.`);
                    sessions.delete(deviceUuid);
                    qrRetryCounters.delete(deviceUuid);
                    retryCounters.delete(deviceUuid);
                    // Hapus session folder karena belum pernah terkoneksi
                    fs.rmSync(sessionPath, { recursive: true, force: true });
                    io.emit(`status-${deviceUuid}`, 'qr_expired');
                    return;
                }
                qrRetryCounters.set(deviceUuid, qrRetries + 1);
                const delay = getBackoffDelay(qrRetries);
                console.log(`[${deviceUuid}] QR timeout (${qrRetries + 1}/${MAX_QR_RETRIES}). Retry dalam ${delay / 1000}s...`);
                setTimeout(() => connectToWhatsApp(deviceUuid), delay);
                return;
            }

            // CASE 3: Disconnect normal (network issue, dll) → reconnect dengan backoff
            const retries = retryCounters.get(deviceUuid) || 0;
            if (retries >= MAX_RETRIES) {
                console.log(`[${deviceUuid}] Gagal reconnect setelah ${MAX_RETRIES}x. Menyerah.`);
                sessions.delete(deviceUuid);
                retryCounters.delete(deviceUuid);

                // Beritahu Laravel: device disconnected
                await notifyLaravel('update-status', {
                    uuid: deviceUuid,
                    status: 'disconnected',
                    phone: null
                });
                io.emit(`status-${deviceUuid}`, 'disconnected');
                return;
            }

            retryCounters.set(deviceUuid, retries + 1);
            const delay = getBackoffDelay(retries);
            console.log(`[${deviceUuid}] Koneksi terputus (${retries + 1}/${MAX_RETRIES}). Reconnect dalam ${delay / 1000}s...`);
            setTimeout(() => connectToWhatsApp(deviceUuid), delay);
            return;
        }

        // ---- Koneksi berhasil ----
        if (connection === 'open') {
            console.log(`[${deviceUuid}] ✅ Terhubung ke WhatsApp!`);
            
            // Reset semua retry counter saat berhasil connect
            retryCounters.delete(deviceUuid);
            qrRetryCounters.delete(deviceUuid);

            io.emit(`status-${deviceUuid}`, 'connected');

            // Beritahu Laravel: device connected
            const userJid = sock.user?.id?.split(':')[0] || 'unknown';
            await notifyLaravel('update-status', {
                uuid: deviceUuid,
                status: 'connected',
                phone: userJid
            });
        }
    });

    sock.ev.on('creds.update', saveCreds);

    // ---- Pesan masuk ----
    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type === 'notify') {
            for (const msg of messages) {
                const from = msg.key.remoteJid;

                // Abaikan status/story broadcast
                if (from === 'status@broadcast') continue;

                if (!msg.key.fromMe && msg.message) {
                    const content = msg.message?.conversation ||
                                    msg.message?.extendedTextMessage?.text ||
                                    (msg.message?.imageMessage ? '[Image]' : '[Media]');

                    console.log(`[${deviceUuid}] 📩 Pesan masuk dari ${from}: ${content}`);

                    // Simpan ke Laravel Database (Inbound)
                    await notifyLaravel('incoming-message', {
                        uuid: deviceUuid,
                        from: from,
                        push_name: msg.pushName || null,
                        content: content,
                        timestamp: msg.messageTimestamp
                    });
                }
            }
        }
    });

    return sock;
}

// ============================================================
// API ENDPOINTS
// ============================================================

// Health Check - untuk monitoring VPS
app.get('/health', (req, res) => {
    const activeSessions = [];
    sessions.forEach((sock, uuid) => {
        activeSessions.push({
            uuid,
            connected: sock.user ? true : false,
            phone: sock.user?.id?.split(':')[0] || null
        });
    });

    res.json({
        status: 'ok',
        uptime: Math.floor((Date.now() - startTime) / 1000),
        activeSessions: activeSessions.length,
        sessions: activeSessions,
        config: {
            laravelUrl: LARAVEL_URL,
            maxRetries: MAX_RETRIES,
            maxQrRetries: MAX_QR_RETRIES
        }
    });
});

// Start / Pair a device
app.post('/sessions/start', async (req, res) => {
    const { device_uuid } = req.body;
    if (!device_uuid) return res.status(400).json({ error: 'device_uuid is required' });

    // Cek jika sudah ada session aktif
    if (sessions.has(device_uuid)) {
        console.log(`[${device_uuid}] Session sudah aktif, skip start`);
        return res.json({ message: 'Session already active', device_uuid });
    }

    try {
        // Reset retry counters untuk fresh start
        retryCounters.delete(device_uuid);
        qrRetryCounters.delete(device_uuid);

        await connectToWhatsApp(device_uuid);
        res.json({ message: 'Session initialization started', device_uuid });
    } catch (err) {
        console.error(`[${device_uuid}] Gagal memulai session:`, err.message);
        res.status(500).json({ error: err.message });
    }
});

// Stop / Disconnect a device
app.post('/sessions/stop', async (req, res) => {
    const { device_uuid } = req.body;
    const sock = sessions.get(device_uuid);

    if (!sock) return res.status(404).json({ error: 'Session not found' });

    try {
        await sock.logout();
        sessions.delete(device_uuid);
        retryCounters.delete(device_uuid);
        qrRetryCounters.delete(device_uuid);
        res.json({ message: 'Session stopped', device_uuid });
    } catch (err) {
        // Force cleanup jika logout gagal
        sessions.delete(device_uuid);
        res.json({ message: 'Session force-stopped', device_uuid });
    }
});

// Send Message
app.post('/messages/send', async (req, res) => {
    const { device_uuid, jid, text } = req.body;
    const sock = sessions.get(device_uuid);

    if (!sock) return res.status(404).json({ error: 'Device session not found or not active' });

    try {
        // Anti-ban: random delay 2-5 detik
        const delay = Math.floor(Math.random() * 3000) + 2000;
        await new Promise(resolve => setTimeout(resolve, delay));

        await sock.sendPresenceUpdate('composing', jid);
        await new Promise(resolve => setTimeout(resolve, 1000));

        const result = await sock.sendMessage(jid, { text });
        res.json({ success: true, result });
    } catch (err) {
        console.error(`[${device_uuid}] Gagal kirim pesan:`, err.message);
        res.status(500).json({ error: err.message });
    }
});

// Get session groups
app.get('/sessions/:uuid/groups', async (req, res) => {
    const sock = sessions.get(req.params.uuid);
    if (!sock) return res.status(404).json({ error: 'Device session not found' });

    try {
        const groups = await sock.groupFetchAllParticipating();
        const groupList = Object.values(groups).map(g => ({
            group_id: g.id,
            group_name: g.subject,
            total_participants: g.participants ? g.participants.length : 0,
            // Field opsional lainnya jika ingin ditambahkan
            phonebook_name: g.subject,
            phonebook_id: g.id
        }));
        res.json({ success: true, groups: groupList });
    } catch (err) {
        console.error(`[${req.params.uuid}] Gagal fetch groups:`, err.message);
        res.status(500).json({ error: err.message });
    }
});

// Get session status
app.get('/sessions/:uuid', (req, res) => {
    const sock = sessions.get(req.params.uuid);
    if (!sock) return res.json({ status: 'inactive' });

    res.json({
        status: 'active',
        connected: sock.user ? true : false,
        phone: sock.user?.id?.split(':')[0] || null
    });
});

// ============================================================
// AUTO-LOAD SESSIONS SAAT STARTUP
// Setiap session dimuat dengan jeda 3 detik untuk menghindari 
// rate limit dari WhatsApp
// ============================================================
async function autoLoadSessions() {
    const sessionDirs = fs.readdirSync(sessionsDir).filter(name => {
        const fullPath = path.join(sessionsDir, name);
        return fs.lstatSync(fullPath).isDirectory();
    });

    if (sessionDirs.length === 0) {
        console.log('[System] Tidak ada session yang perlu dimuat.');
        return;
    }

    console.log(`[System] Memuat ${sessionDirs.length} session...`);

    for (const deviceUuid of sessionDirs) {
        try {
            console.log(`[System] Loading session: ${deviceUuid}`);
            await connectToWhatsApp(deviceUuid);
            // Jeda 3 detik antar session untuk hindari rate limit
            await new Promise(resolve => setTimeout(resolve, 3000));
        } catch (err) {
            console.error(`[System] ❌ Gagal memuat session ${deviceUuid}: ${err.message}`);
            // Lanjutkan ke session berikutnya, jangan crash
        }
    }

    console.log('[System] Semua session selesai dimuat.');
}

// ============================================================
// GRACEFUL SHUTDOWN
// Tutup semua koneksi WA dengan benar saat proses dihentikan 
// (penting untuk PM2 restart/deploy)
// ============================================================
async function gracefulShutdown(signal) {
    console.log(`\n[System] ${signal} diterima. Menutup semua session...`);

    const closePromises = [];
    sessions.forEach((sock, uuid) => {
        console.log(`[System] Menutup session: ${uuid}`);
        closePromises.push(
            sock.end(new Error('Server shutdown'))
                .catch(err => console.error(`[System] Error menutup ${uuid}: ${err.message}`))
        );
    });

    await Promise.allSettled(closePromises);
    sessions.clear();
    retryCounters.clear();
    qrRetryCounters.clear();

    console.log('[System] Semua session ditutup. Goodbye! 👋');
    process.exit(0);
}

process.on('SIGINT', () => gracefulShutdown('SIGINT'));
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));

// ============================================================
// START SERVER
// ============================================================
server.listen(PORT, async () => {
    console.log(`\n🚀 GaweGateway WhatsApp Service`);
    console.log(`   Port       : ${PORT}`);
    console.log(`   Laravel URL: ${LARAVEL_URL}`);
    console.log(`   CORS       : ${CORS_ORIGIN}`);
    console.log(`   Max Retry  : ${MAX_RETRIES}`);
    console.log(`   Max QR Retry: ${MAX_QR_RETRIES}\n`);

    // Load semua session yang sudah ada
    await autoLoadSessions();
});
