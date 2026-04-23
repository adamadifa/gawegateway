@extends('layouts.app')

@section('content')
<div class="mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-bold text-slate-800">Message Logs</h2>
        <div class="flex space-x-4 text-xs font-semibold text-slate-400">
            <button class="hover:text-slate-800 transition">Today</button>
            <button class="hover:text-slate-800 transition">Week</button>
            <button class="px-4 py-1.5 bg-whatsapp text-white rounded-md shadow-sm">Month</button>
            <button class="hover:text-slate-800 transition">Year</button>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left whitespace-nowrap">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/50">
                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Device</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Sender / Recipient</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Status & Dir</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider w-1/3">Content</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($messages as $message)
                <tr class="hover:bg-slate-50 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-6 h-6 rounded-full bg-whatsapp flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase tracking-wider mb-0.5">Gateway</div>
                                <div class="text-xs font-bold text-slate-800">{{ $message->device->name }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider mb-0.5">{{ $message->push_name ?? 'Contact' }}</div>
                        <div class="text-xs font-bold text-slate-800">{{ explode('@', $message->remote_jid)[0] }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider mb-0.5">Status</div>
                        <div class="text-xs font-bold flex items-center space-x-1.5">
                            @if($message->status == 'sent' || $message->status == 'delivered')
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span class="text-slate-800">{{ ucfirst($message->status) }}</span>
                            @else
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                <span class="text-slate-800">{{ ucfirst($message->status) }}</span>
                            @endif
                            <span class="mx-1 text-slate-300">·</span>
                            <span class="{{ $message->direction == 'outbound' ? 'text-whatsapp-dark' : 'text-amber-500' }}">
                                {{ $message->direction }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider mb-0.5">Message</div>
                        <div class="text-xs font-medium text-slate-800 truncate max-w-[200px]" title="{{ $message->content }}">
                            {{ $message->content }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider mb-0.5">Timestamp</div>
                        <div class="text-xs font-bold text-slate-800">{{ $message->created_at->format('Y-m-d H:i') }}</div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button class="p-1 hover:bg-slate-200 rounded text-slate-400 hover:text-slate-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="text-slate-400 text-sm">No messages logged yet.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">
    {{ $messages->links() }}
</div>
@endsection
