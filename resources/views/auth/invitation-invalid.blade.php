<x-layouts.marketing title="Invalid Invitation — NODIVA Operations">
    <div class="py-16 px-4 max-w-md mx-auto text-center space-y-6">
        <div class="w-16 h-16 mx-auto rounded-full bg-[#B42332]/10 border border-[#B42332]/30 flex items-center justify-center text-[#B42332]">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <div class="space-y-2">
            <h1 class="text-xl font-bold text-[#102A36]">Invitation Invalid or Expired</h1>
            <p class="text-xs text-[#61727D] leading-relaxed">
                {{ $reason ?? 'This invitation link is no longer valid, has expired, or has already been accepted.' }}
            </p>
        </div>

        <div class="pt-2">
            <a href="/login" class="inline-block px-5 py-2.5 bg-[#102A36] hover:bg-[#193B49] text-white text-xs font-bold uppercase tracking-wider transition rounded-sm">
                Return to Sign In Portal
            </a>
        </div>
    </div>
</x-layouts.marketing>
