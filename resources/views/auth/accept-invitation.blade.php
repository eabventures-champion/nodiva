<x-layouts.marketing title="Accept Invitation — NODIVA Operations">
    <div class="py-12 px-4 max-w-2xl mx-auto" x-data="{ showPassword: false, showConfirmPassword: false }">
        <div class="bg-white border border-[#D5DEDF] p-8 md:p-10 space-y-6 shadow-sm">
            <!-- Header -->
            <div class="text-center space-y-2 border-b border-[#D5DEDF] pb-5">
                <span class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider bg-[#E8F0EF] px-3 py-1 rounded-sm">
                    Operations Network Onboarding
                </span>
                <h1 class="text-2xl font-bold text-[#102A36]">Accept Console Invitation</h1>
                <p class="text-xs text-[#61727D] max-w-md mx-auto leading-relaxed">
                    You have been invited by <strong>{{ $invitation->inviter->name ?? 'Chief Operations Officer' }}</strong> to join the NODIVA Operations Network team.
                </p>
            </div>

            <!-- Invitation Dossier Summary -->
            <div class="bg-[#F5F3ED] border border-[#D5DEDF] p-5 space-y-3 text-xs">
                <div class="font-bold text-[#102A36] uppercase tracking-wider border-b border-[#D5DEDF] pb-2 text-[11px] flex justify-between">
                    <span>Authorized Assignment Dossier</span>
                    <span class="font-mono text-[#167C78] font-bold">{{ $invitation->role->name ?? 'Operations Officer' }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-[11px]">
                    <div>
                        <span class="text-[#61727D] block">Authorized Email:</span>
                        <strong class="text-[#102A36] font-mono">{{ $invitation->email }}</strong>
                    </div>
                    <div>
                        <span class="text-[#61727D] block">Designated Name:</span>
                        <strong class="text-[#102A36]">{{ $invitation->name ?? 'Team Member' }}</strong>
                    </div>
                    @if ($invitation->job_title)
                    <div class="col-span-2">
                        <span class="text-[#61727D] block">Official Role / Title:</span>
                        <strong class="text-[#102A36]">{{ $invitation->job_title }}</strong>
                    </div>
                    @endif
                </div>

                <!-- Assigned Sidebar Menus List -->
                <div class="pt-2 border-t border-[#D5DEDF] space-y-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] block">
                        Permitted Sidebar Modules & Governance Scopes:
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($sidebarPermissions as $key => $perm)
                            @if ($invitation->hasPermission($key) || $invitation->role?->is_admin)
                                <div class="flex items-center gap-2 p-1.5 bg-white border border-[#D5DEDF] rounded-xs text-[11px]">
                                    <span class="text-[#167C78] font-bold">✓</span>
                                    <span class="text-[#102A36] font-semibold">{{ $perm['name'] }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Errors Banner -->
            @if ($errors->any())
                <div class="p-3 bg-[#B42332]/10 border border-[#B42332]/20 text-xs text-[#B42332] space-y-1">
                    <div class="font-bold">Please correct the following:</div>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Set Password Form -->
            <form method="POST" action="{{ route('invitations.accept', ['token' => $invitation->token]) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-[#172A35] mb-1">
                        Create Secure Password (Minimum 8 characters) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" name="password" required
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
                    <label class="block text-xs font-semibold text-[#172A35] mb-1">
                        Confirm Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showConfirmPassword ? 'text' : 'password'" name="password_confirmation" required
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

                <div class="pt-2">
                    <button type="submit" 
                            class="w-full py-3 bg-[#102A36] text-white text-xs font-bold uppercase tracking-wider hover:bg-[#193B49] rounded-sm transition shadow-sm">
                        Activate Account & Launch Operations Console &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.marketing>
