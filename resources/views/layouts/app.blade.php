<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'GaweGateway') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        whatsapp: {
                            DEFAULT: '#25D366',
                            dark: '#128C7E',
                            light: '#DCF8C6',
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased flex h-screen overflow-hidden">
    
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-slate-100 flex flex-col flex-shrink-0 h-full">
        <!-- Logo -->
        <div class="h-20 flex items-center px-8 border-b border-white">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-whatsapp rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg leading-none">G</span>
                </div>
                <div>
                    <div class="font-bold text-[15px] leading-tight tracking-tight text-slate-900">GAWE</div>
                    <div class="text-[10px] font-bold tracking-[0.2em] text-slate-500">GATEWAY.</div>
                </div>
            </div>
        </div>

        <div class="overflow-y-auto flex-1 py-6 px-4">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-4 px-4">Main Menu</div>
            <nav class="space-y-1">
                <a href="#" class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 border-l-4 border-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-slate-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </a>
                
                <div class="space-y-1">
                    <button class="w-full flex items-center px-4 py-2.5 text-sm font-semibold transition-all duration-200 border-l-4 {{ (request()->routeIs('devices.*') || request()->routeIs('messages.*')) ? 'border-whatsapp text-slate-900 bg-emerald-50/50' : 'border-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                        <svg class="w-5 h-5 mr-3 {{ (request()->routeIs('devices.*') || request()->routeIs('messages.*')) ? 'text-whatsapp-dark' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
                        Gateways
                        <svg class="ml-auto w-4 h-4 transition-transform {{ (request()->routeIs('devices.*') || request()->routeIs('messages.*')) ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div class="{{ (request()->routeIs('devices.*') || request()->routeIs('messages.*')) ? 'block' : 'hidden' }} pl-12 py-1 space-y-1">
                        <a href="{{ route('devices.index') }}" class="flex items-center py-2 text-xs transition-colors {{ request()->routeIs('devices.index') ? 'font-bold text-whatsapp-dark' : 'font-medium text-slate-500 hover:text-slate-900' }}">
                            <span class="w-1.5 h-1.5 rounded-full mr-2.5 {{ request()->routeIs('devices.index') ? 'bg-whatsapp' : 'bg-slate-300' }}"></span> 
                            Devices
                        </a>
                        <a href="{{ route('messages.index') }}" class="flex items-center py-2 text-xs transition-colors {{ request()->routeIs('messages.index') ? 'font-bold text-whatsapp-dark' : 'font-medium text-slate-500 hover:text-slate-900' }}">
                            <span class="w-1.5 h-1.5 rounded-full mr-2.5 {{ request()->routeIs('messages.index') ? 'bg-whatsapp' : 'bg-slate-300' }}"></span> 
                            Logs & Reports
                        </a>
                        <a href="{{ route('messages.create') }}" class="flex items-center py-2 text-xs transition-colors {{ request()->routeIs('messages.create') ? 'font-bold text-whatsapp-dark' : 'font-medium text-slate-500 hover:text-slate-900' }}">
                            <span class="w-1.5 h-1.5 rounded-full mr-2.5 {{ request()->routeIs('messages.create') ? 'bg-whatsapp' : 'bg-slate-300' }}"></span> 
                            Send Broadcast
                        </a>
                    </div>
                </div>
            </nav>

            <div class="mt-8 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-4 px-4">Preferences</div>
            <nav class="space-y-1">
                <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Settings
                </a>
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-slate-50/50">
        <div class="flex-1 overflow-y-auto w-full">
            <div class="max-w-6xl mx-auto px-8 py-8 h-full">
                @yield('content')
            </div>
        </div>
    </main>

    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    @stack('scripts')
</body>
</html>
