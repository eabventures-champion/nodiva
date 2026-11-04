<x-layouts.marketing title="Access Request Pending Review — NODIVA">
    <div class="max-w-2xl mx-auto px-6 py-16">
        <div class="bg-white border border-[#D5DEDF] p-8 shadow-sm text-center space-y-6">
            <div class="w-16 h-16 mx-auto rounded-full bg-[#94611A]/10 border border-[#94611A]/30 flex items-center justify-center text-[#94611A]">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div class="space-y-2">
                <span class="inline-block px-3 py-1 bg-[#94611A]/10 text-[#94611A] text-xs font-bold uppercase tracking-wider rounded-sm">
                    Access Request Submitted • Status: Pending Verification
                </span>
                <h1 class="text-2xl font-bold text-[#102A36]">Your Institutional Access Request is Under Review</h1>
                <p class="text-xs text-[#61727D] leading-relaxed max-w-lg mx-auto">
                    Thank you for applying to connect <strong>{{ $organization->name }}</strong> to the NODIVA Diagnostic Coordination Network.
                </p>
            </div>

            <!-- Summary Details Box -->
            <div class="bg-[#F5F3ED] border border-[#D5DEDF] p-5 text-left text-xs space-y-2.5">
                <div class="font-bold text-[#102A36] uppercase tracking-wider border-b border-[#D5DEDF] pb-2 text-[11px] flex justify-between">
                    <span>Application Dossier</span>
                    <span class="font-mono text-[#61727D]">Ref: {{ $organization->uuid }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[11px]">
                    <div>
                        <span class="text-[#61727D]">Category:</span>
                        <strong class="text-[#102A36] uppercase ml-1">
                            @if ($organization->type->value === 'hospital')
                                Referring Hospital
                            @elseif ($organization->type->value === 'laboratory')
                                Testing Laboratory
                            @elseif ($organization->type->value === 'corporate')
                                Corporate Client
                            @else
                                {{ $organization->type->label() }}
                            @endif
                        </strong>
                    </div>
                    <div>
                        <span class="text-[#61727D]">Designated Lead:</span>
                        <strong class="text-[#102A36] ml-1">{{ $applicant->name ?? auth()->user()?->name }}</strong>
                    </div>
                    <div>
                        <span class="text-[#61727D]">Contact Email:</span>
                        <span class="font-mono text-[#102A36] ml-1">{{ $organization->email }}</span>
                    </div>
                    <div>
                        <span class="text-[#61727D]">Operating City:</span>
                        <span class="text-[#102A36] ml-1">{{ $organization->city }}, Ghana</span>
                    </div>
                    <div class="col-span-2 pt-2 border-t border-[#D5DEDF] flex items-center justify-between">
                        <span class="text-[#61727D]">Administrative Role:</span>
                        <span class="px-2 py-0.5 bg-indigo-50 border border-indigo-200 text-indigo-800 font-bold uppercase text-[10px] rounded-sm">
                            @if ($organization->type->value === 'hospital')
                                Hospital Administrator (hospital_admin)
                            @elseif ($organization->type->value === 'laboratory')
                                Laboratory Administrator (lab_admin)
                            @else
                                Corporate Administrator (corporate_admin)
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Regulatory & Security Safeguard Notice -->
            <div class="bg-[#E8F0EF] border border-[#167C78]/30 p-4 text-left text-xs space-y-1.5">
                <div class="font-bold text-[#167C78] flex items-center gap-1.5 text-[11px] uppercase tracking-wider">
                    <span>🛡️</span>
                    <span>Regulatory & Governance Verification Protocol</span>
                </div>
                @if ($organization->type->value === 'hospital')
                    <p class="text-[#61727D] text-[11px] leading-relaxed">
                        Under the <strong>Ghana Health Service (GHS)</strong> and <strong>Health Institutions & Facilities Act (HEFRA)</strong> compliance framework, NODIVA does not permit unvetted facilities to route clinical patient specimens or request diagnostic tests.
                    </p>
                    <p class="text-[#102A36] text-[11px] leading-relaxed bg-white/70 p-2.5 border border-[#167C78]/20 rounded-sm">
                        <strong>Next Step Upon Platform Approval:</strong> Once approved by the Platform Administrator, sign in as <strong>Hospital Administrator</strong> to access the <strong>Team & Access Governance</strong> console and provision your <strong>Consultant Physicians & Clinicians</strong> (for referrals and results review), <strong>Referral Coordinators</strong> (for specimen custody), and <strong>Finance Officers</strong>.
                    </p>
                @elseif ($organization->type->value === 'laboratory')
                    <p class="text-[#61727D] text-[11px] leading-relaxed">
                        Under the <strong>Allied Health Professions Council (AHPC)</strong> and <strong>HEFRA</strong> diagnostic standards, laboratories must undergo operating license verification, test catalogue audit, and accreditation scope checks before receiving patient specimens or issuing clinical results.
                    </p>
                    <p class="text-[#102A36] text-[11px] leading-relaxed bg-white/70 p-2.5 border border-[#167C78]/20 rounded-sm">
                        <strong>Next Step Upon Platform Approval:</strong> Once approved by the Platform Administrator, sign in as <strong>Laboratory Administrator</strong> to access the <strong>Team & Access Governance</strong> console and provision your <strong>Medical Laboratory Scientists</strong> (for bench testing) and <strong>Consultant Pathologists & Clinical Directors</strong> (for ISO 15189 clinical report release).
                    </p>
                @else
                    <p class="text-[#61727D] text-[11px] leading-relaxed">
                        Under the <strong>Ghana Data Protection Act 2012 (Act 843)</strong> and institutional occupational health rules, corporate accounts undergo legal registration and identity verification prior to scheduling workplace health cohorts or accessing anonymized workforce analytics.
                    </p>
                    <p class="text-[#102A36] text-[11px] leading-relaxed bg-white/70 p-2.5 border border-[#167C78]/20 rounded-sm">
                        <strong>Next Step Upon Platform Approval:</strong> Once approved by the Platform Administrator, sign in as <strong>Corporate Administrator</strong> to access the <strong>Team & Access Governance</strong> console and provision your <strong>Occupational Health Physicians</strong> (for clinical health evaluations) and <strong>HR Coordinators</strong> (for roster scheduling and wellness insights).
                    </p>
                @endif
                <p class="text-[#61727D] text-[11px] leading-relaxed">
                    Our <strong>Central Operations Console</strong> has received an official email notification. A NODIVA verification officer will review your institutional dossier within 1-2 business days. Once approved, you will receive an activation email with your secure login link.
                </p>
            </div>

            <!-- Actions -->
            <div class="pt-2 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="/" class="px-5 py-2.5 bg-[#102A36] hover:bg-[#193B49] text-white text-xs font-bold uppercase tracking-wider transition">
                    Return to Homepage
                </a>
                <a href="/login" class="px-5 py-2.5 border border-[#D5DEDF] bg-white hover:bg-[#F5F3ED] text-[#102A36] text-xs font-bold uppercase tracking-wider transition">
                    Sign In Portal
                </a>
            </div>
        </div>
    </div>

    <script>
        try {
            localStorage.removeItem('nodiva_register_form_draft');
        } catch (e) {}
    </script>
</x-layouts.marketing>
