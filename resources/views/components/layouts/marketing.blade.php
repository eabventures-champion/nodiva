<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'NODIVA — The Network Behind Diagnostics' }}</title>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F5F3ED] text-[#172A35] antialiased min-h-screen flex flex-col font-sans">
    <!-- Restrained Institutional Header -->
    <header x-data="{ mobileMenuOpen: false }" class="border-b border-[#D5DEDF] bg-white sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <a href="/" class="flex items-center space-x-2.5 sm:space-x-3 text-[#102A36]">
                    <!-- Geometric Node-Network Monogram Logo -->
                    <svg class="w-7 h-7 sm:w-8 sm:h-8 text-[#167C78] shrink-0" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="32" height="32" fill="#102A36" rx="2"/>
                        <circle cx="8" cy="8" r="3" fill="#167C78"/>
                        <circle cx="24" cy="8" r="3" fill="#167C78"/>
                        <circle cx="16" cy="16" r="4" fill="#FFFFFF"/>
                        <circle cx="8" cy="24" r="3" fill="#167C78"/>
                        <circle cx="24" cy="24" r="3" fill="#167C78"/>
                        <path d="M8 8L16 16M24 8L16 16M8 24L16 16M24 24L16 16" stroke="#D5DEDF" stroke-width="1.5"/>
                    </svg>
                    <span class="font-bold tracking-wider text-lg sm:text-xl text-[#102A36]">NODIVA</span>
                </a>
                <span class="hidden lg:inline-block border-l border-[#D5DEDF] pl-4 text-xs font-semibold tracking-wider text-[#61727D] uppercase">
                    Institutional Diagnostic Network
                </span>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-6 text-sm font-medium text-[#172A35]">
                <a href="{{ url('/#hospitals') }}" class="hover:text-[#167C78] transition">For Hospitals</a>
                <a href="{{ url('/#laboratories') }}" class="hover:text-[#167C78] transition">For Laboratories</a>
                <a href="{{ url('/#corporate') }}" class="hover:text-[#167C78] transition">Corporate Screening</a>
                <a href="{{ url('/#custody') }}" class="hover:text-[#167C78] transition">Chain of Custody</a>
            </nav>

            <!-- Desktop & Mobile Right Actions -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                @auth
                    <a href="{{ auth()->user()->dashboardUrl() }}" class="px-3 sm:px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white bg-[#102A36] hover:bg-[#193B49] rounded-sm transition inline-flex items-center gap-1.5 shadow-sm">
                        <svg class="w-3.5 h-3.5 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="/login" class="px-3 sm:px-4 py-2 text-xs font-semibold uppercase tracking-wider text-[#102A36] border border-[#D5DEDF] bg-white hover:bg-[#E8F0EF] rounded-sm transition">
                        Sign In
                    </a>
                    <a href="/register" class="hidden sm:inline-block px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white bg-[#102A36] hover:bg-[#193B49] rounded-sm transition">
                        Request Access
                    </a>
                @endauth

                <!-- Mobile Hamburger Toggle Button -->
                <button type="button" 
                        @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="md:hidden p-2 text-[#102A36] hover:text-[#167C78] hover:bg-[#E8F0EF] rounded-sm transition focus:outline-hidden"
                        aria-label="Toggle navigation menu">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer Navigation -->
        <div x-show="mobileMenuOpen" 
             x-cloak 
             @click.away="mobileMenuOpen = false"
             @keydown.escape.window="mobileMenuOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-t border-[#D5DEDF] bg-white px-5 py-4 space-y-4 shadow-lg">
            <nav class="flex flex-col space-y-2.5 text-sm font-medium text-[#172A35]">
                <a href="{{ url('/#hospitals') }}" @click="mobileMenuOpen = false" class="py-2 px-3 rounded-xs hover:bg-[#F5F3ED] hover:text-[#167C78] transition flex items-center justify-between">
                    <span>For Hospitals</span>
                    <span class="text-[#61727D]">&rarr;</span>
                </a>
                <a href="{{ url('/#laboratories') }}" @click="mobileMenuOpen = false" class="py-2 px-3 rounded-xs hover:bg-[#F5F3ED] hover:text-[#167C78] transition flex items-center justify-between">
                    <span>For Laboratories</span>
                    <span class="text-[#61727D]">&rarr;</span>
                </a>
                <a href="{{ url('/#corporate') }}" @click="mobileMenuOpen = false" class="py-2 px-3 rounded-xs hover:bg-[#F5F3ED] hover:text-[#167C78] transition flex items-center justify-between">
                    <span>Corporate Screening</span>
                    <span class="text-[#61727D]">&rarr;</span>
                </a>
                <a href="{{ url('/#custody') }}" @click="mobileMenuOpen = false" class="py-2 px-3 rounded-xs hover:bg-[#F5F3ED] hover:text-[#167C78] transition flex items-center justify-between">
                    <span>Chain of Custody</span>
                    <span class="text-[#61727D]">&rarr;</span>
                </a>
            </nav>

            <div class="pt-3 border-t border-[#D5DEDF] space-y-2">
                @auth
                    <a href="{{ auth()->user()->dashboardUrl() }}" @click="mobileMenuOpen = false" class="w-full py-2.5 text-xs font-semibold uppercase tracking-wider text-white bg-[#102A36] hover:bg-[#193B49] rounded-sm transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Go to Dashboard</span>
                    </a>
                @else
                    <a href="/register" @click="mobileMenuOpen = false" class="w-full py-2.5 text-xs font-semibold uppercase tracking-wider text-white bg-[#167C78] hover:bg-[#167C78]/90 rounded-sm transition flex items-center justify-center">
                        Request Institutional Access
                    </a>
                    <a href="/login" @click="mobileMenuOpen = false" class="w-full py-2.5 text-xs font-semibold uppercase tracking-wider text-[#102A36] border border-[#D5DEDF] bg-[#F5F3ED] hover:bg-white rounded-sm transition flex items-center justify-center">
                        Sign In to Portal
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Professional Restrained Footer -->
    <footer class="border-t border-[#D5DEDF] bg-white text-xs text-[#61727D]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="space-y-3">
                <div class="flex items-center space-x-2 text-[#102A36] font-bold">
                    <span class="tracking-wider">NODIVA</span>
                </div>
                <p class="leading-relaxed">
                    The digital coordination and workflow layer connecting hospital demand, laboratory capacity, and corporate screening programs across accredited facilities.
                </p>
                <div class="text-[11px] text-[#61727D]">
                    Launch Geography: Ghana (Accra, Kumasi, Takoradi)
                </div>
            </div>
            <div>
                <h4 class="font-bold text-[#102A36] uppercase tracking-wider mb-3">Institutional Workspaces</h4>
                <ul class="space-y-2">
                    <li><a href="/login" class="hover:text-[#167C78]">Hospital Referral Portal</a></li>
                    <li><a href="/login" class="hover:text-[#167C78]">Laboratory Intake & Release</a></li>
                    <li><a href="/login" class="hover:text-[#167C78]">Corporate Screening Console</a></li>
                    <li><a href="/login" class="hover:text-[#167C78]">NODIVA Operations Control</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-[#102A36] uppercase tracking-wider mb-3">Clinical Governance</h4>
                <ul class="space-y-2">
                    <li>Qualification & License Verification</li>
                    <li>Chain of Custody & Traceability</li>
                    <li>Encrypted Result Delivery</li>
                    <li>Separation of Analytical Duties</li>
                    <li>Audit Trails & Integrity Hashes</li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-[#102A36] uppercase tracking-wider mb-3">Compliance & Security</h4>
                <p class="leading-relaxed mb-3">
                    Compliant with Data Protection Act, 2012 (Act 843) standards. Zero patient result sharing with unauthorised parties.
                </p>
                <p class="text-[11px]">&copy; 2026 NODIVA Technologies Ltd. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
