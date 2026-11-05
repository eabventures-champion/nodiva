<!DOCTYPE html>
<html lang="en" class="overflow-x-hidden max-w-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'NODIVA Network Workspace' }}</title>
    <style>
        [x-cloak] { display: none !important; }
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
            width: 100% !important;
            position: relative;
            -webkit-text-size-adjust: 100%;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ mobileNavOpen: false }" 
      :class="{ 'overflow-hidden': mobileNavOpen }"
      class="bg-[#F5F3ED] text-[#172A35] antialiased min-h-screen flex flex-col font-sans w-full max-w-full overflow-x-hidden">
    <!-- Top Persistent Institutional Navigation (Fixed) -->
    <header class="fixed top-0 left-0 right-0 h-14 bg-[#102A36] text-white border-b border-[#193B49] flex items-center justify-between px-3 sm:px-6 z-50 shadow-sm flex-shrink-0 w-full max-w-full overflow-hidden">
        @if (session()->has('impersonated_by'))
            <div class="h-1 bg-gradient-to-r from-amber-400 via-amber-300 to-amber-500 w-full absolute top-0 left-0 z-50"></div>
        @endif

        <div class="flex items-center space-x-2 sm:space-x-6">
            <!-- Mobile Hamburger Button -->
            <button type="button" 
                    @click="mobileNavOpen = !mobileNavOpen" 
                    class="lg:hidden p-1.5 -ml-1 text-[#D5DEDF] hover:text-white hover:bg-[#193B49] rounded-sm transition focus:outline-none"
                    aria-label="Toggle navigation menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!mobileNavOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path x-show="mobileNavOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <a href="/" class="flex items-center space-x-2">
                <svg class="w-6 h-6 text-[#167C78] flex-shrink-0" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" fill="#193B49" rx="2"/>
                    <circle cx="8" cy="8" r="3" fill="#167C78"/>
                    <circle cx="24" cy="8" r="3" fill="#167C78"/>
                    <circle cx="16" cy="16" r="4" fill="#FFFFFF"/>
                    <circle cx="8" cy="24" r="3" fill="#167C78"/>
                    <circle cx="24" cy="24" r="3" fill="#167C78"/>
                    <path d="M8 8L16 16M24 8L16 16M8 24L16 16M24 24L16 16" stroke="#D5DEDF" stroke-width="1.5"/>
                </svg>
                <span class="font-bold tracking-wider text-base">NODIVA</span>
            </a>
            
            <a href="{{ route('profile.show') }}#facility-profile"
               title="Click to manage institution profile & facility details"
               class="hidden sm:inline-block border-l border-[#193B49] pl-3 sm:pl-4 text-[11px] sm:text-xs font-semibold text-[#D5DEDF] uppercase tracking-wider hover:text-white hover:underline transition max-w-[130px] sm:max-w-[240px] truncate">
                {{ $organization->name ?? 'Institutional Portal' }}
            </a>
        </div>

        @if (session()->has('impersonated_by'))
            @php
                $impersonatorUser = \App\Models\User::find(session('impersonated_by'));
            @endphp
            <div class="hidden lg:flex items-center gap-2 px-3 py-1 bg-amber-500/15 border border-amber-400/40 rounded-xs text-amber-200 text-xs shadow-inner">
                <span class="inline-block w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span class="font-bold uppercase tracking-wider text-[10px] text-amber-300">CTO Impersonation Active:</span>
                <span class="text-white font-medium">{{ auth()->user()->name }}</span>
                <span class="text-amber-200/80 text-[11px] font-mono">({{ auth()->user()->email }})</span>
                <form method="POST" action="{{ route('ops.impersonate.leave') }}" class="inline ml-1">
                    @csrf
                    <button type="submit" 
                            class="px-2.5 py-0.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-[10px] uppercase tracking-wider rounded-xs transition shadow-xs flex items-center gap-1">
                        <svg class="w-3 h-3 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Exit Impersonation</span>
                    </button>
                </form>
            </div>
        @endif

        <div class="flex items-center space-x-2 sm:space-x-3 text-xs text-[#D5DEDF]">
            @if (session()->has('impersonated_by'))
                <form method="POST" action="{{ route('ops.impersonate.leave') }}" class="inline lg:hidden">
                    @csrf
                    <button type="submit" class="px-2 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-[9px] uppercase tracking-wider rounded-xs transition shadow-sm flex items-center gap-1">
                        <span>Exit</span>
                    </button>
                </form>
            @endif

            <!-- Institutional Notification Bell -->
            <x-notification-bell :organization="$organization ?? null" />

            <a href="{{ route('profile.show') }}" 
               title="View & Edit Institutional Profile"
               class="flex items-center space-x-1.5 sm:space-x-2 bg-[#193B49] hover:bg-[#167C78] px-2 sm:px-3 py-1.5 border border-[#D5DEDF]/20 hover:border-[#167C78] text-[#D5DEDF] hover:text-white transition group shadow-sm text-xs">
                <span class="w-2 h-2 rounded-full bg-[#167C78] group-hover:bg-emerald-300 flex-shrink-0"></span>
                <span class="font-mono font-medium tracking-tight truncate max-w-[80px] sm:max-w-[140px] md:max-w-none">
                    {{ auth()->user()->job_title ?? auth()->user()->name }}
                </span>
                <svg class="w-3.5 h-3.5 text-[#A2B7C1] group-hover:text-white opacity-80 group-hover:opacity-100 transition hidden sm:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </a>
            <form method="POST" action="/logout" class="inline">
                @csrf
                <button type="submit" 
                        class="hover:text-white uppercase font-bold text-[10px] sm:text-[11px] tracking-wider text-[#D5DEDF] px-1.5 sm:px-2 py-1.5 hover:bg-[#193B49] transition"
                        title="Sign out of console">
                    <span class="hidden sm:inline">Sign Out</span>
                    <svg class="w-4 h-4 sm:hidden text-[#D5DEDF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </header>

    <div class="flex-grow flex pt-14 min-h-screen relative w-full max-w-full overflow-x-hidden">
        @if (isset($sidebar))
            <!-- Mobile Backdrop Overlay -->
            <div x-show="mobileNavOpen" 
                 x-cloak
                 @click="mobileNavOpen = false"
                 class="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs lg:hidden transition-opacity"
                 x-transition:enter="transition-opacity ease-linear duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
            </div>

            <!-- Responsive Sidebar Drawer: Fixed Off-Canvas on Mobile (<lg), Sticky on Desktop (>=lg) -->
            <div class="fixed inset-y-0 left-0 z-50 pt-14 w-64 bg-white transform transition-transform duration-300 ease-in-out lg:static lg:z-auto lg:pt-0 lg:translate-x-0 lg:flex-shrink-0 flex flex-col"
                 :class="mobileNavOpen ? 'translate-x-0 shadow-2xl visible pointer-events-auto' : '-translate-x-full invisible pointer-events-none lg:visible lg:pointer-events-auto lg:translate-x-0'"
                 @click="if ($event.target.closest('a')) mobileNavOpen = false"
                 @keydown.escape.window="mobileNavOpen = false">
                
                <!-- Mobile Drawer Header with Close Button -->
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-[#D5DEDF] bg-[#102A36] text-white lg:hidden flex-shrink-0">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-[#167C78]"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-white">Menu Navigation</span>
                    </div>
                    <button type="button" @click="mobileNavOpen = false" class="p-1 text-[#D5DEDF] hover:text-white rounded-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex-grow overflow-y-auto">
                    {{ $sidebar }}
                </div>
            </div>
        @endif

        <!-- Main Workspace View (Locked Horizontal Scrolling) -->
        <main class="flex-grow p-3.5 sm:p-6 lg:p-8 bg-[#F5F3ED] overflow-y-auto overflow-x-hidden w-full max-w-full min-w-0">
            <div class="max-w-6xl mx-auto space-y-6 w-full max-w-full min-w-0 overflow-x-hidden">
                @if (session('success'))
                    <div class="p-3 bg-[#247451]/10 border border-[#247451]/30 text-xs font-semibold text-[#247451] flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#247451] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="p-3 bg-[#B42332]/10 border border-[#B42332]/30 text-xs font-semibold text-[#B42332] flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#B42332] shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>
            <div class="max-w-6xl mx-auto space-y-6">
                @if (session('success'))
                    <div class="p-3 bg-[#247451]/10 border border-[#247451]/30 text-xs font-semibold text-[#247451] flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#247451] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="p-3 bg-[#B42332]/10 border border-[#B42332]/30 text-xs font-semibold text-[#B42332] flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#B42332] shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
