<x-layouts.marketing title="Set New Password — NODIVA Institutional Network">
    <div class="py-16 flex items-center justify-center px-4" x-data="{ showPassword: false, showConfirmPassword: false }">
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
                        <button type="button" @click="showPassword = !showPassword" tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#61727D] hover:text-[#102A36] focus:outline-none"
                                aria-label="Toggle password visibility">
                            <svg x-show="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPassword" style="display: none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-[#172A35] mb-1">
                        Confirm New Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showConfirmPassword ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                               class="w-full px-3 py-2 pr-10 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword" tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#61727D] hover:text-[#102A36] focus:outline-none"
                                aria-label="Toggle password confirmation visibility">
                            <svg x-show="!showConfirmPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showConfirmPassword" style="display: none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
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
