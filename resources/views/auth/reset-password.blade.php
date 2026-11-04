<x-layouts.marketing title="Set New Password — NODIVA Institutional Network">
    <div class="py-16 flex items-center justify-center px-4" x-data="{ showPassword: false }">
        <div class="max-w-md w-full bg-white border border-[#D5DEDF] p-8 space-y-6 shadow-sm">
            <div class="text-center space-y-2">
                <span class="text-[10px] font-bold text-[#167C78] uppercase tracking-wider bg-[#E8F0EF] px-2.5 py-0.5 rounded-sm">
                    Credential Update
                </span>
                <h1 class="text-2xl font-bold text-[#102A36]">Set New Password</h1>
                <p class="text-xs text-[#61727D] leading-relaxed">
                    Create a secure password (minimum 8 characters) for your NODIVA institutional account.
                </p>
            </div>

            @if ($errors->any())
                <div class="p-3 bg-[#B42332]/10 border border-[#B42332]/20 text-xs text-[#B42332] rounded-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-[#172A35] mb-1">
                        Institutional Email Address
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required readonly
                           class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm bg-[#F5F3ED] text-[#61727D] focus:outline-none cursor-not-allowed">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-[#172A35] mb-1">
                        New Password (Minimum 8 characters) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autofocus
                               class="w-full px-3 py-2 pr-10 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#61727D] hover:text-[#102A36] focus:outline-none">
                            <span class="text-[10px] uppercase font-bold" x-text="showPassword ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-[#172A35] mb-1">
                        Confirm New Password <span class="text-red-500">*</span>
                    </label>
                    <input :type="showPassword ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                           class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                </div>

                <button type="submit" 
                        class="w-full py-2.5 bg-[#102A36] text-white text-xs font-bold uppercase tracking-wider hover:bg-[#193B49] rounded-sm transition shadow-sm">
                    Update Password &amp; Secure Account &rarr;
                </button>
            </form>

            <div class="border-t border-[#D5DEDF] pt-4 text-center">
                <a href="{{ route('login') }}" class="text-xs font-semibold text-[#167C78] hover:underline">
                    &larr; Return to Sign In
                </a>
            </div>
        </div>
    </div>
</x-layouts.marketing>
