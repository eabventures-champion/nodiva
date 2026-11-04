<x-layouts.marketing title="Security Session Expired (419) — NODIVA">
    <div class="max-w-xl mx-auto px-6 py-16">
        <div class="bg-white border border-[#D5DEDF] p-8 shadow-sm text-center space-y-6">
            <!-- Icon -->
            <div class="w-16 h-16 mx-auto rounded-full bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <!-- Header & Details -->
            <div class="space-y-2">
                <span class="inline-block px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold uppercase tracking-wider rounded-xs">
                    419 • Security Session Expired
                </span>
                <h1 class="text-2xl font-bold text-[#102A36]">Page Session Expired</h1>
                <p class="text-xs text-[#61727D] leading-relaxed max-w-md mx-auto">
                    Your form submission could not be verified because the page was left open for too long. For healthcare compliance and account security, security tokens automatically expire during inactivity.
                </p>
            </div>

            <!-- Helpful Explanation Box -->
            <div class="p-4 bg-[#F5F3ED] border border-[#D5DEDF] text-xs text-[#61727D] text-left leading-relaxed space-y-2">
                <div class="font-bold text-[#102A36] uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>How to resolve this</span>
                </div>
                <p>
                    Click the <strong>Refresh & Try Again</strong> button below to generate a fresh security token and resubmit your details, or return to the sign-in portal.
                </p>
            </div>

            <!-- Clickable Action Buttons -->
            <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('login') }}" 
                   class="px-5 py-2.5 bg-[#167C78] hover:bg-[#136360] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition inline-flex items-center gap-2 cursor-pointer shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Refresh & Sign In</span>
                </a>

                <a href="{{ route('home') }}" 
                   class="px-5 py-2.5 border border-[#D5DEDF] bg-white hover:bg-[#F5F3ED] text-[#102A36] text-xs font-bold uppercase tracking-wider rounded-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</x-layouts.marketing>
