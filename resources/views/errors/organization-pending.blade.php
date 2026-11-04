<x-layouts.marketing title="Access Pending Verification — NODIVA">
    <div class="max-w-xl mx-auto px-6 py-16">
        <div class="bg-white border border-[#D5DEDF] p-8 shadow-sm text-center space-y-6">
            <div class="w-16 h-16 mx-auto rounded-full bg-[#94611A]/10 border border-[#94611A]/30 flex items-center justify-center text-[#94611A]">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <div class="space-y-2">
                <span class="inline-block px-3 py-1 bg-[#94611A]/10 text-[#94611A] text-xs font-bold uppercase tracking-wider rounded-sm">
                    Workspace Inactive • Verification Required
                </span>
                <h1 class="text-2xl font-bold text-[#102A36]">Workspace Pending Platform Activation</h1>
                <p class="text-xs text-[#61727D] leading-relaxed max-w-md mx-auto">
                    The institutional account for <strong>{{ $organization->name ?? 'your organization' }}</strong> is currently in <strong>PENDING</strong> status awaiting regulatory confirmation from the NODIVA Central Operations Console.
                </p>
            </div>

            <div class="p-4 bg-[#F5F3ED] border border-[#D5DEDF] text-xs text-[#61727D] text-left leading-relaxed space-y-2">
                <div class="font-bold text-[#102A36] uppercase tracking-wider text-[11px]">Why is my account pending?</div>
                <p>
                    Under national clinical diagnostic regulations (Act 843 & HEFRA), hospital and laboratory credentials must be independently confirmed before clinical patient referrals and specimen chain-of-custody tracking are unlocked.
                </p>
                <p>
                    If you have already submitted your credentials, an Operations Officer will review them shortly. For urgent inquiries, please contact <strong>ops@nodiva.com</strong>.
                </p>
            </div>

            <div class="pt-2 flex justify-center gap-3">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 bg-[#102A36] hover:bg-[#193B49] text-white text-xs font-bold uppercase tracking-wider transition">
                        Sign Out
                    </button>
                </form>
                <a href="/" class="px-5 py-2.5 border border-[#D5DEDF] bg-white hover:bg-[#F5F3ED] text-[#102A36] text-xs font-bold uppercase tracking-wider transition">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</x-layouts.marketing>
