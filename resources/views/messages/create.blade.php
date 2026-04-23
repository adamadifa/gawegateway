@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto mt-4">
    <div class="flex items-center space-x-4 mb-8">
        <a href="{{ route('devices.index') }}" class="p-2 border border-slate-200 text-slate-400 hover:text-slate-800 hover:border-slate-300 rounded-lg transition bg-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Send Broadcast</h1>
            <p class="text-sm font-medium text-slate-500">Send an instant message to any WhatsApp number.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-600 rounded-xl flex items-center space-x-3 shadow-sm">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <p class="font-semibold text-sm">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-600 rounded-xl flex items-center space-x-3 shadow-sm">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <p class="font-semibold text-sm">{{ session('error') }}</p>
    </div>
    @endif

    <div class="bg-white p-8 rounded-[1.5rem] shadow-sm border border-slate-100">
        <form action="{{ route('messages.send') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 gap-6">
                <!-- Select Device -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Sender Gateway</label>
                    <select name="device_uuid" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:border-whatsapp focus:ring-1 focus:ring-whatsapp transition appearance-none font-medium text-sm">
                        <option value="">Select an active gateway</option>
                        @foreach($devices as $device)
                        <option value="{{ $device->uuid }}">{{ $device->name }} ({{ $device->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Recipient Phone -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Recipient Phone Number</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-slate-400 font-medium">+</span>
                        <input type="text" name="phone" required placeholder="e.g. 628123456789" class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-4 py-3 text-slate-800 focus:outline-none focus:border-whatsapp focus:ring-1 focus:ring-whatsapp transition font-medium text-sm">
                    </div>
                    <p class="mt-2 text-[11px] font-medium text-slate-400">Use international format without the '+' symbol.</p>
                </div>

                <!-- Message Content -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Message Content</label>
                    <textarea name="message" rows="5" required placeholder="Type your message here..." class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:border-whatsapp focus:ring-1 focus:ring-whatsapp transition font-medium text-sm"></textarea>
                </div>

                <!-- Submit -->
                <button type="submit" class="w-full py-4 mt-2 bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-xl transition shadow-md flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    <span>Send Message</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
