<x-layouts.marketing title="Request Access — NODIVA Institutional Network">
    <div class="py-12 px-4 max-w-3xl mx-auto">
        <div class="bg-white border border-[#D5DEDF] p-8 md:p-10 space-y-8 shadow-sm" x-data="{
            storageKey: 'nodiva_register_form_draft',
            selectedType: @js(old('type', $initialType)),
            showPassword: false,
            showConfirmPassword: false,
            hasInitialQuery: @js(request()->has('type')),
            hasServerOldInput: @js($errors->any() || old('organization_name') !== null),
            formData: {
                organization_name: @js(old('organization_name', '')),
                registration_number: @js(old('registration_number', '')),
                organization_email: @js(old('organization_email', '')),
                organization_phone: @js(old('organization_phone', '+233')),
                city: @js(old('city', 'Accra')),
                service_region_id: @js(old('service_region_id', '')),
                name: @js(old('name', '')),
                job_title: @js(old('job_title', '')),
                email: @js(old('email', '')),
                consent_data_protection: @js((bool) old('consent_data_protection', false))
            },
            hasDraft: false,

            init() {
                try {
                    const saved = localStorage.getItem(this.storageKey);
                    if (saved) {
                        const parsed = JSON.parse(saved);
                        if (!this.hasInitialQuery && parsed.selectedType && !this.hasServerOldInput) {
                            this.selectedType = parsed.selectedType;
                        }
                        
                        let restoredAny = false;
                        for (const key in this.formData) {
                            // If not already filled by server-side old() error redirect, restore draft from storage
                            if (!this.formData[key] || this.formData[key] === '+233' || this.formData[key] === 'Accra' || !this.hasServerOldInput) {
                                if (parsed[key] !== undefined && parsed[key] !== '') {
                                    this.formData[key] = parsed[key];
                                    restoredAny = true;
                                }
                            }
                        }
                        if (restoredAny) {
                            this.hasDraft = true;
                        }
                    }
                } catch (e) {
                    console.error('Draft load error', e);
                }

                // Automatically save every keystroke & field update into localStorage
                this.$watch('selectedType', () => this.saveToStorage());
                this.$watch('formData', () => this.saveToStorage(), { deep: true });
            },

            saveToStorage() {
                try {
                    const payload = {
                        selectedType: this.selectedType,
                        ...this.formData
                    };
                    localStorage.setItem(this.storageKey, JSON.stringify(payload));
                    this.hasDraft = true;
                } catch (e) {}
            },

            clearDraft() {
                try {
                    localStorage.removeItem(this.storageKey);
                    this.formData = {
                        organization_name: '',
                        registration_number: '',
                        organization_email: '',
                        organization_phone: '+233',
                        city: 'Accra',
                        service_region_id: '',
                        name: '',
                        job_title: '',
                        email: '',
                        consent_data_protection: false
                    };
                    this.hasDraft = false;
                } catch (e) {}
            }
        }">
            <!-- Header -->
            <div class="text-center space-y-2">
                <span class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider bg-[#E8F0EF] px-3 py-1 rounded-sm">
                    Institutional Onboarding
                </span>
                <h1 class="text-2xl md:text-3xl font-bold text-[#102A36]">Request Institutional Access</h1>
                <p class="text-xs text-[#61727D] max-w-lg mx-auto leading-relaxed">
                    Join Ghana's verified healthcare diagnostic network connecting hospitals and corporate wellness programs to accredited testing laboratories.
                </p>
            </div>

            <!-- Auto-save notification banner -->
            <div x-show="hasDraft" style="display: none;" 
                 class="flex items-center justify-between bg-[#E8F0EF] border border-[#167C78]/30 px-3.5 py-2 text-xs text-[#167C78] rounded-sm transition">
                <span class="flex items-center gap-1.5 font-medium">
                    <svg class="w-4 h-4 text-[#167C78] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Form draft auto-saved. Your entries are saved locally and preserved across page refreshes.</span>
                </span>
                <button type="button" @click="clearDraft()" 
                        class="text-[10px] text-[#94611A] hover:underline uppercase font-bold tracking-wider ml-2 flex-shrink-0">
                    Clear Draft
                </button>
            </div>

            <!-- Organization Type Selector Tabs -->
            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-[#102A36]">
                    Select Institutional Account Category
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <!-- Hospital Option -->
                    <button type="button" @click="selectedType = 'hospital'"
                            :class="selectedType === 'hospital' ? 'border-[#167C78] bg-[#E8F0EF]/40 ring-1 ring-[#167C78]' : 'border-[#D5DEDF] bg-white hover:bg-[#F5F3ED]'"
                            class="p-4 border text-left transition rounded-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-[#102A36]">Referring Hospital</span>
                                <span x-show="selectedType === 'hospital'" class="text-[#167C78] font-bold text-xs">✓</span>
                            </div>
                            <p class="text-[11px] text-[#61727D] mt-1 leading-snug">
                                Clinicians and referral coordinators sending out specialized diagnostic orders.
                            </p>
                        </div>
                        <div class="mt-3 text-[10px] font-mono text-[#167C78] uppercase font-bold">
                            Hospital Portal
                        </div>
                    </button>

                    <!-- Laboratory Option -->
                    <button type="button" @click="selectedType = 'laboratory'"
                            :class="selectedType === 'laboratory' ? 'border-[#167C78] bg-[#E8F0EF]/40 ring-1 ring-[#167C78]' : 'border-[#D5DEDF] bg-white hover:bg-[#F5F3ED]'"
                            class="p-4 border text-left transition rounded-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-[#102A36]">Testing Laboratory</span>
                                <span x-show="selectedType === 'laboratory'" class="text-[#167C78] font-bold text-xs">✓</span>
                            </div>
                            <p class="text-[11px] text-[#61727D] mt-1 leading-snug">
                                Accredited diagnostic facilities receiving specimens, processing tests & releasing results.
                            </p>
                        </div>
                        <div class="mt-3 text-[10px] font-mono text-[#167C78] uppercase font-bold">
                            Laboratory Portal
                        </div>
                    </button>

                    <!-- Corporate Option -->
                    <button type="button" @click="selectedType = 'corporate'"
                            :class="selectedType === 'corporate' ? 'border-[#167C78] bg-[#E8F0EF]/40 ring-1 ring-[#167C78]' : 'border-[#D5DEDF] bg-white hover:bg-[#F5F3ED]'"
                            class="p-4 border text-left transition rounded-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-[#102A36]">Corporate Client</span>
                                <span x-show="selectedType === 'corporate'" class="text-[#167C78] font-bold text-xs">✓</span>
                            </div>
                            <p class="text-[11px] text-[#61727D] mt-1 leading-snug">
                                Employers & occupational health teams booking employee health screenings and wellness packages.
                            </p>
                        </div>
                        <div class="mt-3 text-[10px] font-mono text-[#167C78] uppercase font-bold">
                            Corporate Portal
                        </div>
                    </button>
                </div>
            </div>

            <!-- Errors Banner -->
            @if ($errors->any())
                <div class="p-4 bg-[#B42332]/10 border border-[#B42332]/20 text-xs text-[#B42332] space-y-1">
                    <div class="font-bold">Please correct the following:</div>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="/register" @submit="saveToStorage()" class="space-y-6">
                @csrf
                <input type="hidden" name="type" :value="selectedType">

                <!-- Section 1: Institutional Profile -->
                <div class="space-y-4 pt-2 border-t border-[#D5DEDF]">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#102A36] text-white flex items-center justify-center text-xs font-bold">1</span>
                        <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">
                            <span x-show="selectedType === 'hospital'">Hospital / Health Facility Information</span>
                            <span x-show="selectedType === 'laboratory'">Diagnostic Laboratory Information</span>
                            <span x-show="selectedType === 'corporate'">Corporate Organization Information</span>
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                <span x-show="selectedType === 'hospital'">Hospital / Clinic Legal Name</span>
                                <span x-show="selectedType === 'laboratory'">Laboratory / Diagnostic Center Legal Name</span>
                                <span x-show="selectedType === 'corporate'">Company / Corporate Entity Name</span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="organization_name" x-model="formData.organization_name" required
                                   :placeholder="selectedType === 'hospital' ? 'e.g. Ridge Specialist Hospital' : (selectedType === 'laboratory' ? 'e.g. SynLab Diagnostics West Africa' : 'e.g. Tullow Oil Ghana Ltd')"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                <span x-show="selectedType === 'hospital'">HEFRA / MOH Facility Licence No.</span>
                                <span x-show="selectedType === 'laboratory'">AHPC / HEFRA Operating Licence No.</span>
                                <span x-show="selectedType === 'corporate'">Registrar General (RGD) Business No.</span>
                            </label>
                            <input type="text" name="registration_number" x-model="formData.registration_number"
                                   :placeholder="selectedType === 'hospital' ? 'e.g. HEFRA/HOSP/2026/041' : (selectedType === 'laboratory' ? 'e.g. AHPC/LAB/2026/089' : 'e.g. CS-99120-2026')"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                Institutional Billing / Operational Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="organization_email" x-model="formData.organization_email" required
                                   placeholder="contact@institution.com"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                Primary Telephone Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="organization_phone" x-model="formData.organization_phone" required
                                   placeholder="+233 30 200 0000"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                City / Municipality <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="city" x-model="formData.city" required
                                   placeholder="e.g. Accra, Kumasi, Takoradi"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                Operational Service Region (Ghana)
                            </label>
                            <select name="service_region_id" x-model="formData.service_region_id" class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-white">
                                <option value="">-- Choose Primary Operating Region --</option>
                                @foreach ($serviceRegions as $region)
                                    <option value="{{ $region->id }}">{{ $region->name }} ({{ $region->country_code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Primary Administrator / Contact Lead -->
                <div class="space-y-4 pt-4 border-t border-[#D5DEDF]">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#102A36] text-white flex items-center justify-center text-xs font-bold">2</span>
                        <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">
                            <span x-show="selectedType === 'hospital'">Hospital Administrator Credentials (Institutional Lead)</span>
                            <span x-show="selectedType === 'laboratory'">Laboratory Administrator Credentials (Institutional Lead)</span>
                            <span x-show="selectedType === 'corporate'">Corporate Administrator Credentials (Institutional Lead)</span>
                        </h2>
                    </div>

                    <!-- Hospital Administrator Role Callout -->
                    <div x-show="selectedType === 'hospital'" class="p-3 bg-[#E8F0EF] border border-[#167C78]/30 rounded-sm text-xs space-y-1.5">
                        <div class="font-bold text-[#167C78] flex items-center gap-1.5 uppercase tracking-wider text-[11px]">
                            <svg class="w-4 h-4 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            Hospital Administrator Role Provisioning
                        </div>
                        <p class="text-[#102A36] text-[11px] leading-relaxed">
                            You are registering as the designated <strong>Hospital Administrator</strong>. Upon approval by the Platform Administrator, your account will have full authority in the <strong>Team & Access Governance</strong> console to onboard your clinical facility's personnel:
                        </p>
                        <ul class="list-disc list-inside text-[11px] text-[#61727D] pl-1 space-y-0.5">
                            <li><strong class="text-[#102A36]">Consultant Physicians & Clinicians</strong> — Provisioned under the <em>Clinical Referral Protocol</em> for patient referrals and diagnostic result reviews.</li>
                            <li><strong class="text-[#102A36]">Referral Coordinators & Phlebotomists</strong> — Provisioned under the <em>Specimen Custody Protocol</em> for phlebotomy and courier dispatch.</li>
                            <li><strong class="text-[#102A36]">Hospital Finance Officers</strong> — Provisioned under the <em>Billing & Settlement Protocol</em> for settlement statement auditing and payments.</li>
                        </ul>
                    </div>

                    <!-- Laboratory Administrator Role Callout -->
                    <div x-show="selectedType === 'laboratory'" class="p-3 bg-[#E8F0EF] border border-[#167C78]/30 rounded-sm text-xs space-y-1.5">
                        <div class="font-bold text-[#167C78] flex items-center gap-1.5 uppercase tracking-wider text-[11px]">
                            <svg class="w-4 h-4 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Laboratory Administrator Role Provisioning
                        </div>
                        <p class="text-[#102A36] text-[11px] leading-relaxed">
                            You are registering as the designated <strong>Laboratory Administrator</strong>. Upon approval by the Platform Administrator, your account will have full authority in the <strong>Team & Access Governance</strong> console to onboard your clinical facility's personnel:
                        </p>
                        <ul class="list-disc list-inside text-[11px] text-[#61727D] pl-1 space-y-0.5">
                            <li><strong class="text-[#102A36]">Medical Laboratory Scientists</strong> — Provisioned under the <em>Analytical Testing Protocol</em> for specimen accessioning and bench results entry.</li>
                            <li><strong class="text-[#102A36]">Consultant Pathologists & Clinical Directors</strong> — Provisioned under the <em>Clinical Release Protocol (ISO 15189)</em> to authorize and release findings to referring hospitals.</li>
                        </ul>
                    </div>

                    <!-- Corporate Administrator Role Callout -->
                    <div x-show="selectedType === 'corporate'" class="p-3 bg-[#E8F0EF] border border-[#167C78]/30 rounded-sm text-xs space-y-1.5">
                        <div class="font-bold text-[#167C78] flex items-center gap-1.5 uppercase tracking-wider text-[11px]">
                            <svg class="w-4 h-4 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Corporate Administrator Role Provisioning
                        </div>
                        <p class="text-[#102A36] text-[11px] leading-relaxed">
                            You are registering as the designated <strong>Corporate Administrator</strong>. Upon approval by the Platform Administrator, your account will have full authority in the <strong>Team & Access Governance</strong> console to onboard your corporate organization's personnel:
                        </p>
                        <ul class="list-disc list-inside text-[11px] text-[#61727D] pl-1 space-y-0.5">
                            <li><strong class="text-[#102A36]">Occupational Health Physicians</strong> — Provisioned under the <em>Occupational Medicine Protocol</em> for clinical evaluation of employee health screenings where consented.</li>
                            <li><strong class="text-[#102A36]">HR Coordinators & Wellness Leads</strong> — Provisioned under the <em>Workplace Health Scheduling Protocol</em> for roster scheduling and aggregate insights.</li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                Administrator Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" x-model="formData.name" required
                                   placeholder="e.g. Dr. Kojo Mensah"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                Designation / Role <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="job_title" x-model="formData.job_title" required
                                   :placeholder="selectedType === 'hospital' ? 'e.g. Medical Director / Chief Medical Officer / Administrator' : (selectedType === 'laboratory' ? 'e.g. Laboratory Administrator / General Manager' : 'e.g. Head of HR / Occupational Health Director / Administrator')"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                Administrator Sign-In Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" x-model="formData.email" required
                                   placeholder="lead@institution.com"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                            <p class="text-[11px] text-[#61727D] mt-1">This email will be used for your secure institutional login.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                Password (Minimum 8 characters) <span class="text-red-500">*</span>
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
                    </div>
                </div>

                <!-- Section 3: Governance & Data Protection Agreement -->
                <div class="pt-4 border-t border-[#D5DEDF] space-y-3">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="consent_data_protection" value="1" x-model="formData.consent_data_protection" required
                               class="mt-0.5 text-[#167C78] focus:ring-[#167C78] rounded-sm">
                        <span class="text-xs text-[#102A36] leading-relaxed">
                            <strong>Institutional Compliance Undertaking:</strong> Our institution certifies that all diagnostic orders, clinical results, and patient health data handled via NODIVA comply with the Ghana Data Protection Act 2012 (Act 843), HEFRA standards, and the NODIVA Institutional Network Operating Rules.
                        </span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full py-3 bg-[#102A36] text-white text-xs font-bold uppercase tracking-wider hover:bg-[#193B49] rounded-sm transition shadow-sm">
                    <span x-show="selectedType === 'hospital'">Submit Hospital Access Request for Verification</span>
                    <span x-show="selectedType === 'laboratory'">Submit Laboratory Onboarding Request for Verification</span>
                    <span x-show="selectedType === 'corporate'">Submit Corporate Program Request for Verification</span>
                </button>
            </form>

            <!-- Back to Sign In -->
            <div class="border-t border-[#D5DEDF] pt-4 text-center">
                <span class="text-xs text-[#61727D]">Already have authorized access?</span>
                <a href="/login" class="text-xs font-semibold text-[#167C78] hover:underline ml-1">Sign In to your Portal</a>
            </div>
        </div>
    </div>
</x-layouts.marketing>
