@extends('layouts.app')

@section('content')
<div class="flex justify-between items-center mb-8">
    <div class="flex items-center space-x-2 text-xl font-bold">
        <span class="text-slate-900">Gateways</span>
        <span class="text-slate-300">→</span>
        <span class="text-slate-400 font-medium">Gateway List</span>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-5 py-2.5 bg-whatsapp hover:bg-whatsapp-dark text-white font-semibold text-sm rounded-lg transition shadow-sm flex items-center space-x-2">
        <svg class="w-5 h-5 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <span>Add Gateway</span>
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    @forelse($devices as $device)
    @php
        $totalOutbound = $device->messages()->where('direction', 'outbound')->count();
        $successOutbound = $device->messages()->where('direction', 'outbound')->whereIn('status', ['sent', 'delivered'])->count();
        $failedOutbound = $device->messages()->where('direction', 'outbound')->where('status', 'failed')->count();
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden flex flex-col hover:shadow-md transition-all duration-300">
        <!-- Header -->
        <div class="p-5 border-b border-slate-50 flex items-start justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center border border-emerald-100 flex-shrink-0">
                    <svg class="w-5 h-5 text-whatsapp" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 leading-tight">{{ $device->name }}</h3>
                    <p class="text-slate-400 text-[11px] font-medium leading-tight mt-0.5">{{ $device->phone ?? 'Unpaired Device' }}</p>
                </div>
            </div>
            
            <div class="flex flex-col items-end space-y-2">
                @if($device->status == 'connected')
                    <div class="flex items-center space-x-1.5 bg-emerald-50 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-tight">Active</span>
                    </div>
                @else
                    <div class="flex items-center space-x-1.5 bg-slate-50 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tight">Offline</span>
                    </div>
                @endif
                
                <form action="{{ route('devices.destroy', $device->uuid) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this gateway? All associated data will be removed.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-all duration-200" title="Delete Device">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Stats -->
        <div class="px-5 py-4 bg-slate-50/30">
            <div class="grid grid-cols-3 gap-2">
                <div class="text-center p-2 rounded-lg bg-white border border-slate-100 shadow-sm">
                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Total</div>
                    <div class="text-sm font-black text-slate-800">{{ number_format($totalOutbound) }}</div>
                </div>
                <div class="text-center p-2 rounded-lg bg-white border border-slate-100 shadow-sm">
                    <div class="text-[9px] font-bold text-emerald-500 uppercase tracking-wider mb-0.5">Sent</div>
                    <div class="text-sm font-black text-emerald-600">{{ number_format($successOutbound) }}</div>
                </div>
                <div class="text-center p-2 rounded-lg bg-white border border-slate-100 shadow-sm">
                    <div class="text-[9px] font-bold text-rose-400 uppercase tracking-wider mb-0.5">Failed</div>
                    <div class="text-sm font-black text-rose-500">{{ number_format($failedOutbound) }}</div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="p-4 mt-auto flex items-center justify-end border-t border-slate-50">
            <div class="flex space-x-2">
                @if($device->status == 'connected')
                    <a href="{{ route('messages.index') }}" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition-all flex items-center">
                        Logs
                    </a>
                @else
                    <a href="{{ route('devices.scan', $device->uuid) }}" class="px-3.5 py-1.5 bg-whatsapp hover:bg-whatsapp-dark text-white text-xs font-bold rounded-lg transition-all shadow-sm shadow-whatsapp/20 flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                        Scan QR
                    </a>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full py-24 text-center bg-white rounded-[1.5rem] border border-slate-100 shadow-sm">
        <div class="w-20 h-20 bg-emerald-50 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-emerald-100">
            <svg class="w-10 h-10 text-whatsapp" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-slate-800 mb-2">No Gateways Found</h3>
        <p class="text-slate-500 text-sm max-w-sm mx-auto mb-6">You haven't paired any WhatsApp devices yet. Start sending by adding your first gateway.</p>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-6 py-2.5 bg-whatsapp hover:bg-whatsapp-dark text-white font-semibold rounded-lg shadow-md transition inline-flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Add Your First Gateway</span>
        </button>
    </div>
    @endforelse
</div>

<!-- Modal -->
<div id="addModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-6 bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md p-8 rounded-2xl shadow-2xl animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-slate-800">Add New Gateway</h2>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="{{ route('devices.store') }}" method="POST">
            @csrf
            <div class="mb-6">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Gateway Assignment Name</label>
                <input type="text" name="name" required placeholder="e.g. Finance Division, Support Bot..." class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:border-whatsapp focus:ring-1 focus:ring-whatsapp transition">
            </div>
            <div class="flex space-x-4">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold rounded-lg transition">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-lg transition shadow-md">Initialize Gateway</button>
            </div>
        </form>
    </div>
</div>
@endsection
