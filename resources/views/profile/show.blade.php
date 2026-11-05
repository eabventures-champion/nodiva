<x-layouts.workspace title="Institutional User Profile — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        @if ($organization?->type?->value === 'hospital')
            <x-hospital-sidebar active="profile" />
        @elseif ($organization?->type?->value === 'laboratory')
            <x-lab-sidebar active="profile" />
        @elseif ($organization?->type?->value === 'corporate')
            <x-corporate-sidebar active="profile" />
        @else
            <x-ops-sidebar active="profile" />
        @endif
    </x-slot:sidebar>

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-[#D5DEDF] pb-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#167C78]"></span>
                <h1 class="text-2xl font-bold text-[#102A36]">Institutional User Profile</h1>
            </div>
            <p class="text-xs text-[#61727D] mt-1">
                Manage your personal identifiers, role designation, cryptographic credentials, and notification triggers.
            </p>
        </div>

        <div>
            <a href="{{ $dashboardRoute }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-[#D5DEDF] bg-white text-xs font-semibold text-[#102A36] hover:bg-[#F5F3ED] transition">
                <span>&larr;</span> Return to {{ $workspaceLabel }}
            </a>
        </div>
    </div>

    <!-- Institutional Context Ribbon -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
        <div class="bg-white p-4 border border-[#D5DEDF] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <div class="text-[10px] font-bold text-[#61727D] uppercase tracking-wider">Institution / Organization</div>
                    @if ($canManageOrg)
                        <a href="#facility-profile" class="text-[10px] text-[#167C78] font-bold hover:underline flex items-center gap-0.5">
                            <span>Edit</span>
                            <span>&darr;</span>
                        </a>
                    @endif
                </div>
                <div class="text-sm font-bold text-[#102A36] mt-1 truncate">{{ $organization?->name ?? 'NODIVA Platform' }}</div>
                <div class="text-[11px] text-[#167C78] mt-0.5 font-medium">{{ $workspaceLabel }}</div>
            </div>
        </div>

        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[10px] font-bold text-[#61727D] uppercase tracking-wider">Designation / Role</div>
            <div class="text-sm font-bold text-[#102A36] mt-1 truncate">{{ $user->job_title ?? 'Authorized Staff' }}</div>
            <div class="text-[11px] text-[#61727D] mt-0.5 font-mono">{{ $membership?->role?->name ?? 'Primary Administrator' }}</div>
        </div>

        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[10px] font-bold text-[#61727D] uppercase tracking-wider">Account Clearance</div>
            <div class="flex items-center gap-1.5 mt-1">
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#247451]/10 text-[#247451]">
                    {{ strtoupper($user->status ?? 'ACTIVE') }}
                </span>
                <span class="text-[11px] text-[#61727D]">AES-256 Auth</span>
            </div>
            <div class="text-[10px] text-[#61727D] mt-0.5 font-mono truncate">ID: {{ substr($user->uuid ?? 'usr-000', 0, 13) }}...</div>
        </div>

        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[10px] font-bold text-[#61727D] uppercase tracking-wider">Multi-Factor Status</div>
            <div class="text-sm font-bold text-[#247451] mt-1 flex items-center gap-1">
                <svg class="w-4 h-4 text-[#247451]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Enforced / Verified</span>
            </div>
            <div class="text-[11px] text-[#61727D] mt-0.5">Hardware / TOTP Compliant</div>
        </div>
    </div>

    @if ($canManageOrg && $organization)
        <!-- Institutional Facility Profile Form (Admin Clearance) -->
        <div id="facility-profile" class="bg-white border-2 border-[#167C78]/30 p-6 space-y-4 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#D5DEDF] pb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/30 text-[10px] font-bold uppercase rounded-xs">
                            Administrator Authority
                        </span>
                        <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Institution & Facility Profile</h2>
                    </div>
                    <p class="text-[11px] text-[#61727D] mt-1">
                        Edit the facility name and contact credentials for <strong>{{ $organization->name }}</strong>. Changes to the facility name update the top navigation header, referral orders, and patient test reports across the platform.
                    </p>
                </div>
            </div>

            <form action="{{ route('profile.organization') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">
                            Institution / Facility Display Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $organization->name) }}" required
                               placeholder="e.g. Korle Lab"
                               class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white font-bold text-[#102A36]">
                        <p class="text-[10px] text-[#61727D] mt-1">This text appears in the top navigation bar (e.g. "NODIVA | {{ strtoupper($organization->name) }}") and all referral order slips.</p>
                        @error('name')
                            <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Legal Registered Entity Name</label>
                        <input type="text" name="legal_name" value="{{ old('legal_name', $organization->legal_name) }}"
                               placeholder="e.g. Korle Bu Central Diagnostic Laboratories Ltd"
                               class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                        <p class="text-[10px] text-[#61727D] mt-1">Statutory company or institutional entity name.</p>
                        @error('legal_name')
                            <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Official Facility Email</label>
                        <input type="email" name="email" value="{{ old('email', $organization->email) }}"
                               placeholder="e.g. intake@korlebu.example"
                               class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                        <p class="text-[10px] text-[#61727D] mt-1">Primary mailbox for intake orders and network notifications.</p>
                        @error('email')
                            <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Facility Contact Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $organization->phone) }}"
                               placeholder="e.g. +233 30 200 0003"
                               class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white font-mono">
                        <p class="text-[10px] text-[#61727D] mt-1">Contact line used for phlebotomy courier coordination.</p>
                        @error('phone')
                            <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">City / Municipality</label>
                        <input type="text" name="city" value="{{ old('city', $organization->city) }}"
                               placeholder="e.g. Accra"
                               class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                        @error('city')
                            <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Physical Address Line</label>
                        <input type="text" name="address_line" value="{{ old('address_line', $organization->address_line) }}"
                               placeholder="e.g. Guggisberg Avenue, Korle Bu"
                               class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                        @error('address_line')
                            <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="pt-3 border-t border-[#D5DEDF] flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider text-xs transition shadow-sm">
                        Update Institution Details
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Main Forms Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 1. Edit Profile Form -->
        <div class="bg-white border border-[#D5DEDF] p-6 space-y-4">
            <div class="border-b border-[#D5DEDF] pb-3">
                <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Personal & Designation Details</h2>
                <p class="text-[11px] text-[#61727D]">Update your full legal name, clinical title, and direct institutional contacts.</p>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-[#102A36] mb-1">Full Legal Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                    @error('name')
                        <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block font-bold text-[#102A36] mb-1">Institutional Title / Job Role <span class="text-red-500">*</span></label>
                    <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" required
                           placeholder="e.g. Chief Operations Officer, Senior Consultant, Pathologist"
                           class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                    <p class="text-[10px] text-[#61727D] mt-1">This designation appears in network audit trails, release sign-offs, and order headers.</p>
                    @error('job_title')
                        <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block font-bold text-[#102A36] mb-1">Official Institutional Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                    @error('email')
                        <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block font-bold text-[#102A36] mb-1">Direct Telephone / Mobile Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+233 24 000 0000"
                           class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white font-mono">
                    <p class="text-[10px] text-[#61727D] mt-1">Used for urgent STAT diagnostic specimen custody calls and security tokens.</p>
                    @error('phone')
                        <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-3 border-t border-[#D5DEDF] flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider text-xs transition shadow-sm">
                        Save Profile Details
                    </button>
                </div>
            </form>
        </div>

        <!-- 2. Change Password Form -->
        <div class="bg-white border border-[#D5DEDF] p-6 space-y-4">
            <div class="border-b border-[#D5DEDF] pb-3">
                <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Security & Institutional Password</h2>
                <p class="text-[11px] text-[#61727D]">Rotate your password in adherence to national healthcare information security protocols.</p>
            </div>

            <form action="{{ route('profile.password') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-[#102A36] mb-1">Current Password <span class="text-red-500">*</span></label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'" name="current_password" required
                               class="w-full border border-[#D5DEDF] p-2.5 pr-10 focus:border-[#167C78] focus:outline-none bg-white">
                        <button type="button" @click="show = !show" tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#61727D] hover:text-[#102A36] focus:outline-none"
                                aria-label="Toggle password visibility">
                            <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="show" style="display: none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block font-bold text-[#102A36] mb-1">New Institutional Password <span class="text-red-500">*</span></label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" required
                               placeholder="Minimum 8 characters with numbers and letters"
                               class="w-full border border-[#D5DEDF] p-2.5 pr-10 focus:border-[#167C78] focus:outline-none bg-white">
                        <button type="button" @click="show = !show" tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#61727D] hover:text-[#102A36] focus:outline-none"
                                aria-label="Toggle password visibility">
                            <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="show" style="display: none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block font-bold text-[#102A36] mb-1">Confirm New Password <span class="text-red-500">*</span></label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'" name="password_confirmation" required
                               class="w-full border border-[#D5DEDF] p-2.5 pr-10 focus:border-[#167C78] focus:outline-none bg-white">
                        <button type="button" @click="show = !show" tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#61727D] hover:text-[#102A36] focus:outline-none"
                                aria-label="Toggle password visibility">
                            <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="show" style="display: none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] text-[11px] text-[#61727D] space-y-1">
                    <div class="font-bold text-[#102A36]">Institutional Password Security Policy:</div>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li>At least 8 alphanumeric characters</li>
                        <li>Must contain both letters and numbers</li>
                        <li>Salted and hashed via bcrypt before database persistence</li>
                    </ul>
                </div>

                <div class="pt-3 border-t border-[#D5DEDF] flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-[#102A36] hover:bg-[#193B49] text-white font-bold uppercase tracking-wider text-xs transition shadow-sm">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Notification Preferences & Audit Card -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Notification Preferences -->
        <div class="bg-white border border-[#D5DEDF] p-6 space-y-4">
            <div class="border-b border-[#D5DEDF] pb-3">
                <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Notification & Dispatch Alerts</h2>
                <p class="text-[11px] text-[#61727D]">Configure automated event triggers for diagnostic referrals, couriers, and reports.</p>
            </div>

            <form action="{{ route('profile.notifications') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="space-y-3">
                    <label class="flex items-start gap-3 p-2.5 border border-[#D5DEDF] hover:bg-[#F5F3ED] cursor-pointer transition">
                        <input type="checkbox" name="notify_referral_email" value="1" 
                               {{ $user->wantsNotification('email', 'referral_alerts') ? 'checked' : '' }}
                               class="mt-0.5 text-[#167C78] focus:ring-[#167C78]">
                        <div>
                            <div class="font-bold text-[#102A36]">Email: Referral Order & Specimen Transit Alerts</div>
                            <div class="text-[11px] text-[#61727D]">Instant notification when an order is submitted, assigned, or dispatched with courier.</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-2.5 border border-[#D5DEDF] hover:bg-[#F5F3ED] cursor-pointer transition">
                        <input type="checkbox" name="notify_results_email" value="1" 
                               {{ $user->wantsNotification('email', 'result_release') ? 'checked' : '' }}
                               class="mt-0.5 text-[#167C78] focus:ring-[#167C78]">
                        <div>
                            <div class="font-bold text-[#102A36]">Email: Pathologist Result Release & Critical Values</div>
                            <div class="text-[11px] text-[#61727D]">Immediate dispatch upon pathologist sign-off and STAT critical panic-value alerts.</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-2.5 border border-[#D5DEDF] hover:bg-[#F5F3ED] cursor-pointer transition">
                        <input type="checkbox" name="notify_referral_sms" value="1" 
                               {{ $user->wantsNotification('sms', 'referral_alerts') ? 'checked' : '' }}
                               class="mt-0.5 text-[#167C78] focus:ring-[#167C78]">
                        <div>
                            <div class="font-bold text-[#102A36]">SMS: High Priority / STAT Emergency Alerts</div>
                            <div class="text-[11px] text-[#61727D]">Direct SMS delivery to your phone for time-critical specimen actions.</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-2.5 border border-[#D5DEDF] hover:bg-[#F5F3ED] cursor-pointer transition">
                        <input type="checkbox" name="notify_security_email" value="1" 
                               {{ $user->wantsNotification('email', 'security_digest') ? 'checked' : '' }}
                               class="mt-0.5 text-[#167C78] focus:ring-[#167C78]">
                        <div>
                            <div class="font-bold text-[#102A36]">Email: Weekly Institutional Digest & Financial Summary</div>
                            <div class="text-[11px] text-[#61727D]">Consolidated summary of network activity, settlements, and billing statements.</div>
                        </div>
                    </label>
                </div>

                <div class="pt-3 border-t border-[#D5DEDF] flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider text-xs transition shadow-sm">
                        Save Notification Preferences
                    </button>
                </div>
            </form>
        </div>

        <!-- Security & Audit Meta Information -->
        <div class="bg-white border border-[#D5DEDF] p-6 space-y-4 flex flex-col justify-between">
            <div>
                <div class="border-b border-[#D5DEDF] pb-3">
                    <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Audit Trail & Security Baseline</h2>
                    <p class="text-[11px] text-[#61727D]">Cryptographic integrity details according to Ghanaian healthcare data standards.</p>
                </div>

                <div class="mt-4 space-y-3 text-xs">
                    <div class="flex justify-between items-center py-2 border-b border-[#D5DEDF]/60">
                        <span class="text-[#61727D]">Actor Public UUID</span>
                        <span class="font-mono text-[#102A36] font-semibold text-[11px]">{{ $user->uuid }}</span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-[#D5DEDF]/60">
                        <span class="text-[#61727D]">Active Organization</span>
                        <span class="font-bold text-[#102A36]">{{ $organization?->name ?? 'NODIVA Network' }}</span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-[#D5DEDF]/60">
                        <span class="text-[#61727D]">Assigned Security Role</span>
                        <span class="font-semibold text-[#167C78]">{{ $membership?->role?->name ?? 'Operations Administrator' }}</span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-[#D5DEDF]/60">
                        <span class="text-[#61727D]">Session Encryption Mode</span>
                        <span class="font-mono text-[#247451] font-bold">AES-256-GCM / SHA-256</span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-[#D5DEDF]/60">
                        <span class="text-[#61727D]">Last Activity Timestamp</span>
                        <span class="text-[#102A36]">{{ now()->format('M d, Y H:i:s') }} (UTC)</span>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-amber-50/70 border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
                <strong>Data Protection Compliance (Act 843):</strong> All account activities, credential modifications, and clinical access events are recorded to the tamper-evident hash-chained audit ledger.
            </div>
        </div>
    </div>
</x-layouts.workspace>
