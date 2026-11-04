<x-layouts.marketing title="Sign In — NODIVA Institutional Network">
    <div class="py-16 flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white border border-[#D5DEDF] p-8 space-y-6">
            <div class="text-center space-y-2">
                <h1 class="text-2xl font-bold text-[#102A36]">Institutional Sign In</h1>
                <p class="text-xs text-[#61727D]">
                    Access your authorized Hospital, Laboratory, Corporate, or Operations portal.
                </p>
            </div>

            @if (session('status'))
                <div class="p-3 bg-[#247451]/10 border border-[#247451]/30 text-xs text-[#247451] font-medium rounded-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="p-3 bg-amber-50 border border-amber-200 text-xs text-amber-900 font-medium rounded-sm flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-3 bg-[#B42332]/10 border border-[#B42332]/20 text-xs text-[#B42332]">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="/login" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-[#172A35] mb-1">
                        Institutional Email
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                </div>

                <div x-data="{ showPassword: false }">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-[#172A35] mb-1">
                        Password
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                            class="w-full px-3 py-2 pr-10 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#61727D] hover:text-[#102A36] focus:outline-none"
                            aria-label="Toggle password visibility">
                            <!-- Eye icon (when hidden) -->
                            <svg x-show="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <!-- Eye slash icon (when visible) -->
                            <svg x-show="showPassword" style="display: none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center space-x-2 text-[#61727D] cursor-pointer select-none">
                        <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}
                            class="w-4 h-4 rounded-sm border-[#D5DEDF] text-[#167C78] focus:ring-[#167C78] focus:ring-offset-0 cursor-pointer accent-[#167C78]">
                        <span class="hover:text-[#102A36]">Remember session</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-[#167C78] hover:underline">Forgot password?</a>
                </div>

                <button type="submit" class="w-full py-2.5 bg-[#102A36] text-white text-xs font-bold uppercase tracking-wider hover:bg-[#193B49] rounded-sm transition">
                    Sign In
                </button>
            </form>

            <div class="border-t border-[#D5DEDF] pt-4 text-center">
                <span class="text-xs text-[#61727D]">New organization?</span>
                <a href="/register" class="text-xs font-semibold text-[#167C78] hover:underline ml-1">Request access credentials</a>
            </div>
        </div>
    </div>
</x-layouts.marketing>
