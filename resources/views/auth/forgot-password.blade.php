<x-layouts.marketing title="Reset Password — NODIVA Institutional Network">
    <div class="py-16 flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white border border-[#D5DEDF] p-8 space-y-6 shadow-sm">
            <div class="text-center space-y-2">
                <span class="text-[10px] font-bold text-[#167C78] uppercase tracking-wider bg-[#E8F0EF] px-2.5 py-0.5 rounded-sm">
                    Account Security
                </span>
                <h1 class="text-2xl font-bold text-[#102A36]">Reset Your Password</h1>
                <p class="text-xs text-[#61727D] leading-relaxed">
                    Enter your institutional email address below and we'll dispatch a secure, single-use password reset link.
                </p>
            </div>

            @if (session('status'))
                <div class="p-3 bg-[#247451]/10 border border-[#247451]/30 text-xs text-[#247451] font-semibold rounded-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-3 bg-[#B42332]/10 border border-[#B42332]/20 text-xs text-[#B42332] rounded-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-[#172A35] mb-1">
                        Institutional Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="e.g. clinician@institution.com"
                           class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                </div>

                <button type="submit" 
                        class="w-full py-2.5 bg-[#102A36] text-white text-xs font-bold uppercase tracking-wider hover:bg-[#193B49] rounded-sm transition shadow-sm">
                    Send Password Reset Link &rarr;
                </button>
            </form>

            <div class="border-t border-[#D5DEDF] pt-4 text-center">
                <span class="text-xs text-[#61727D]">Remember your credentials?</span>
                <a href="{{ route('login') }}" class="text-xs font-semibold text-[#167C78] hover:underline ml-1">
                    Return to Sign In
                </a>
            </div>
        </div>
    </div>
</x-layouts.marketing>
