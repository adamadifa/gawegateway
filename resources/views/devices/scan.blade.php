@extends('layouts.app')

@section('content')
<div class="mb-8">
    <div class="flex items-center space-x-2 text-xl font-bold">
        <span class="text-slate-900">Gateways</span>
        <span class="text-slate-300">→</span>
        <span class="text-slate-400 font-medium">Connect Device</span>
    </div>
</div>

<div class="max-w-2xl mx-auto mt-12 text-center">
    <h1 class="text-3xl font-bold text-slate-800 mb-2">Connect WhatsApp</h1>
    <p class="text-slate-500 mb-10">Scan the QR code below using your WhatsApp mobile app.</p>
    
    <div class="relative inline-block p-6 bg-white rounded-3xl mb-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100">
        <!-- QR Code Placeholder -->
        <div id="qrcode-container" class="w-64 h-64 flex items-center justify-center bg-slate-50 rounded-2xl overflow-hidden border border-slate-100">
            <div id="loader" class="animate-spin rounded-full h-10 w-10 border-4 border-whatsapp border-t-transparent"></div>
            <img id="qrcode-img" src="" alt="QR Code" class="hidden w-full h-full object-contain p-2">
        </div>
        
        <!-- Success Overlay -->
        <div id="success-overlay" class="hidden absolute inset-0 bg-emerald-500/90 backdrop-blur-sm rounded-3xl flex flex-col items-center justify-center text-white">
            <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            <p class="font-bold text-xl">Connected!</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 text-left max-w-sm mx-auto bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <h4 class="text-xs font-bold uppercase text-slate-400 mb-2 tracking-wider">Instructions</h4>
        <div class="flex items-center space-x-4 text-sm font-medium text-slate-600">
            <span class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-emerald-50 text-whatsapp-dark rounded-full font-bold text-xs">1</span>
            <span>Open WhatsApp on your phone</span>
        </div>
        <div class="flex items-center space-x-4 text-sm font-medium text-slate-600">
            <span class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-emerald-50 text-whatsapp-dark rounded-full font-bold text-xs">2</span>
            <span>Tap Menu or Settings and select Linked Devices</span>
        </div>
        <div class="flex items-center space-x-4 text-sm font-medium text-slate-600">
            <span class="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-emerald-50 text-whatsapp-dark rounded-full font-bold text-xs">3</span>
            <span>Point your phone to this screen to capture the code</span>
        </div>
    </div>

    <div class="mt-10">
        <a href="{{ route('devices.index') }}" class="text-slate-500 hover:text-whatsapp-dark font-medium transition underline underline-offset-4 text-sm">Back to Devices</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const WA_SERVICE_URL = "{{ env('WA_GATEWAY_PUBLIC_URL', 'http://localhost:3001') }}";
    const socket = io(WA_SERVICE_URL);
    const deviceUuid = "{{ $device->uuid }}";
    
    const qrImg = document.getElementById('qrcode-img');
    const loader = document.getElementById('loader');
    const successOverlay = document.getElementById('success-overlay');

    // Subscribe to events for this specific device
    socket.on(`qr-${deviceUuid}`, (data) => {
        qrImg.src = data;
        qrImg.classList.remove('hidden');
        loader.classList.add('hidden');
    });

    socket.on(`status-${deviceUuid}`, (status) => {
        if (status === 'connected') {
            successOverlay.classList.remove('hidden');
            setTimeout(() => {
                window.location.href = "{{ route('devices.index') }}";
            }, 2000);
        }
        if (status === 'qr_expired') {
            // QR sudah expired, tampilkan pesan
            loader.classList.add('hidden');
            qrImg.classList.add('hidden');
            document.getElementById('qrcode-container').innerHTML = `
                <div class="text-center p-4">
                    <svg class="w-10 h-10 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-sm font-bold text-slate-600 mb-1">QR Code Expired</p>
                    <p class="text-xs text-slate-400 mb-3">QR tidak discan tepat waktu.</p>
                    <button onclick="location.reload()" class="px-4 py-1.5 bg-whatsapp hover:bg-whatsapp-dark text-white text-xs font-bold rounded-lg transition">Coba Lagi</button>
                </div>`;
        }
    });

    // Request start if not active
    fetch(`${WA_SERVICE_URL}/sessions/start`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ device_uuid: deviceUuid })
    });
</script>
@endpush
