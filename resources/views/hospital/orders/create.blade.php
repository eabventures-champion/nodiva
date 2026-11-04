<x-layouts.workspace title="Create New Diagnostic Referral — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-hospital-sidebar active="new" />
    </x-slot:sidebar>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#102A36]">Create New Diagnostic Referral</h1>
            <p class="text-xs text-[#61727D]">Initiate automated laboratory capacity matching with verified accreditation and sample chain of custody.</p>
        </div>
        <a href="{{ route('hospital.dashboard') }}" class="text-xs text-[#167C78] hover:underline font-semibold flex items-center gap-1">
            &larr; Back to Dashboard
        </a>
    </div>


    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
            <div class="font-bold">Please correct the following errors:</div>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('hospital.orders.store') }}" method="POST" x-data="{
        patientMode: 'new',
        searchQuery: '',
        selectedTests: @js($preselectedTestId ? [(string)$preselectedTestId] : []),
        priority: 'routine',
        collectionMethod: 'courier',
        routingMode: 'broadcast',
        specificLabId: '{{ $laboratories->first()?->id ?? '' }}',
        labNames: @js($laboratories->pluck('name', 'id')),
        labCoverage: @js($labCoverage ?? []),
        customDirectDispatch: null,
        get currentSelectedLab() {
            return this.labCoverage[this.specificLabId] || null;
        },
        get selectedLabName() {
            return this.labNames[this.specificLabId] || 'Selected Partner Laboratory';
        },
        get directDispatchCharge() {
            if (this.customDirectDispatch !== null) return this.customDirectDispatch;
            return this.currentSelectedLab ? this.currentSelectedLab.dispatch_charge : '35.00';
        },
        set directDispatchCharge(val) {
            this.customDirectDispatch = val;
        },
        onSpecificLabChange() {
            this.customDirectDispatch = null;
        },
        get qualifiedLabs() {
            if (this.selectedTests.length === 0) return Object.values(this.labCoverage);
            return Object.values(this.labCoverage).filter(lab => {
                return this.selectedTests.every(testId => lab.available_test_ids.includes(String(testId)));
            });
        },
        get selectedLabQualified() {
            if (this.selectedTests.length === 0) return true;
            const lab = this.labCoverage[this.specificLabId];
            if (!lab) return false;
            return this.selectedTests.every(testId => lab.available_test_ids.includes(String(testId)));
        },
        get selectedLabCoverageCount() {
            if (this.selectedTests.length === 0) return 0;
            const lab = this.labCoverage[this.specificLabId];
            if (!lab) return 0;
            return this.selectedTests.filter(testId => lab.available_test_ids.includes(String(testId))).length;
        },
        get smartRouteMatch() {
            const list = this.qualifiedLabs.length > 0 ? this.qualifiedLabs : Object.values(this.labCoverage);
            if (list.length === 0) return null;
            const sorted = [...list].sort((a, b) => {
                if (a.is_same_region && !b.is_same_region) return -1;
                if (!a.is_same_region && b.is_same_region) return 1;
                return parseFloat(b.reliability_score) - parseFloat(a.reliability_score);
            });
            return sorted[0];
        },
        get smartRouteDispatchCharge() {
            return this.smartRouteMatch ? this.smartRouteMatch.dispatch_charge : '35.00';
        },
        get computedLogisticsCharge() {
            if (this.collectionMethod === 'walk_in') return '0.00';
            if (this.routingMode === 'broadcast') return '';
            if (this.routingMode === 'direct') return this.directDispatchCharge;
            if (this.routingMode === 'automated') return this.smartRouteDispatchCharge;
            return '35.00';
        },
        get logisticsChargeSummary() {
            if (this.collectionMethod === 'walk_in') return 'GHS 0.00 (Exempt)';
            if (this.routingMode === 'broadcast') return 'Pending Lab Acceptance';
            if (this.routingMode === 'direct') return 'GHS ' + this.directDispatchCharge;
            if (this.routingMode === 'automated') return 'GHS ' + this.smartRouteDispatchCharge;
            return 'GHS 35.00';
        }
    }" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Clinical Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Section 1: Patient Identity -->
                <div class="bg-white border border-[#D5DEDF] p-6 space-y-4">
                    <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-[#102A36] text-white flex items-center justify-center text-xs font-bold">1</span>
                            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Patient Identification & Consent</h2>
                        </div>
                        <div class="flex gap-4 text-xs font-semibold">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="patient_mode" value="new" x-model="patientMode" class="text-[#167C78] focus:ring-[#167C78]">
                                <span>New Patient</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="patient_mode" value="existing" x-model="patientMode" class="text-[#167C78] focus:ring-[#167C78]">
                                <span>Existing Hospital Patient</span>
                            </label>
                        </div>
                    </div>

                    <!-- Existing Patient Select -->
                    <div x-show="patientMode === 'existing'" class="space-y-2">
                        <label class="block text-xs font-bold text-[#102A36]">Select Patient Record</label>
                        <select name="existing_patient_id" class="w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                            <option value="">-- Choose registered patient --</option>
                            @foreach ($patients as $p)
                                <option value="{{ $p->id }}">{{ $p->reference }} — {{ $p->first_name }} {{ $p->last_name }} (MRN: {{ $p->medical_record_number }})</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-[#61727D]">Hospital records are strictly tenant-isolated; PII is encrypted at rest using AES-256 GCM.</p>
                    </div>

                    <!-- New Patient Inputs -->
                    <div x-show="patientMode === 'new'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#102A36]">Hospital MRN / File Number <span class="text-red-500">*</span></label>
                            <input type="text" name="medical_record_number" value="{{ old('medical_record_number') }}" placeholder="e.g. MRN-2026-0842"
                                   class="mt-1 w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#102A36]">Contact Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone', '+233') }}" placeholder="+233 24 000 0000"
                                   class="mt-1 w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#102A36]">First Name <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="First Name"
                                   class="mt-1 w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#102A36]">Last Name <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Last Name"
                                   class="mt-1 w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#102A36]">Date of Birth <span class="text-red-500">*</span></label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                                   class="mt-1 w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#102A36]">Biological Sex <span class="text-red-500">*</span></label>
                            <select name="sex" class="mt-1 w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                                <option value="male" {{ old('sex') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('sex') === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('sex') === 'other' ? 'selected' : '' }}>Other / Intersex</option>
                            </select>
                        </div>
                    </div>

                    <!-- Consent Confirmation Checkbox -->
                    <div class="mt-4 pt-3 border-t border-[#D5DEDF]">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="consent_confirmed" value="1" checked required
                                   class="mt-0.5 text-[#167C78] focus:ring-[#167C78] rounded-sm">
                            <span class="text-xs text-[#102A36]">
                                <strong>Informed Referral Consent Granted:</strong> Patient has been counselled and consented in writing to external laboratory processing and specimen transport in accordance with the Ghana Data Protection Act 2012 (Act 843).
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Section 2: Clinical Details & Indication -->
                <div class="bg-white border border-[#D5DEDF] p-6 space-y-4">
                    <div class="flex items-center gap-2 border-b border-[#D5DEDF] pb-3">
                        <span class="w-6 h-6 rounded-full bg-[#102A36] text-white flex items-center justify-center text-xs font-bold">2</span>
                        <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Clinical Details & Logistics</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-[#102A36]">Clinical Indication / Suspected Diagnosis <span class="text-red-500">*</span></label>
                            <input type="text" name="clinical_indication" value="{{ old('clinical_indication') }}"
                                   placeholder="e.g. Assessment of chronic viral hepatitis, suspected liver fibrosis"
                                   class="mt-1 w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#102A36]">Order Priority</label>
                            <select name="priority" x-model="priority" class="mt-1 w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                                <option value="routine">Routine Turnaround</option>
                                <option value="urgent">Urgent / STAT Priority</option>
                            </select>
                            <p class="text-[11px] text-[#61727D] mt-1">Urgent orders trigger expedited courier pickup & prioritized laboratory queue.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#102A36]">Sample Collection Method</label>
                            <select name="collection_method" x-model="collectionMethod" class="mt-1 w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none bg-white">
                                <option value="courier">Hospital Phlebotomy (Courier Pickup)</option>
                                <option value="walk_in">Patient Direct Walk-In (Partner Laboratory)</option>
                            </select>
                            <p class="text-[11px] text-[#61727D] mt-1">NODIVA coordinates cold-chain courier transport directly from hospital phlebotomy.</p>
                        </div>


                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-[#102A36]">Confidential Clinical Notes (Encrypted)</label>
                            <textarea name="clinical_notes" rows="2" placeholder="Medication history, relevant imaging findings, fasting status..."
                                      class="mt-1 w-full text-xs border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none">{{ old('clinical_notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Diagnostic Test Selection -->
                <div class="bg-white border border-[#D5DEDF] p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-[#D5DEDF] pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-[#102A36] text-white flex items-center justify-center text-xs font-bold">3</span>
                            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Select Requested Diagnostic Tests <span class="text-red-500">*</span></h2>
                        </div>
                        <input type="text" x-model="searchQuery" placeholder="Filter tests by name or code..."
                               class="text-xs border border-[#D5DEDF] px-3 py-1.5 focus:border-[#167C78] focus:outline-none w-full sm:w-64">
                    </div>

                    <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                        @foreach ($tests as $test)
                            <div class="p-3 border border-[#D5DEDF] hover:border-[#167C78] transition bg-white flex items-start gap-3"
                                 x-show="!searchQuery || '{{ strtolower($test->name . ' ' . $test->code . ' ' . ($test->specialty?->name ?? '')) }}'.includes(searchQuery.toLowerCase())">
                                <input type="checkbox" name="test_ids[]" value="{{ $test->id }}" id="test_{{ $test->id }}"
                                       x-model="selectedTests"
                                       class="mt-1 text-[#167C78] focus:ring-[#167C78] rounded-sm">
                                <label for="test_{{ $test->id }}" class="flex-1 cursor-pointer">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="font-bold text-xs text-[#102A36]">{{ $test->name }}</div>
                                        <div class="text-[10px] font-mono uppercase bg-[#F5F3ED] px-2 py-0.5 text-[#61727D] border border-[#D5DEDF]">
                                            {{ $test->code }}
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 mt-1.5 text-[11px] text-[#61727D]">
                                        <span>Specialty: <strong class="text-[#102A36]">{{ $test->specialty?->name }}</strong></span>
                                        <span>•</span>
                                        <span>Specimen: <strong class="text-[#167C78]">{{ $test->specimenType?->container ?? 'Standard Container' }}</strong> ({{ $test->specimenType?->transport_requirements ?? 'ambient' }})</span>
                                        @if ($test->catalogueEntries->isNotEmpty())
                                            <span>•</span>
                                            <span>Standard TAT: <strong class="text-[#102A36]">{{ $test->catalogueEntries->min('turnaround_hours') }}h</strong></span>
                                        @endif
                                    </div>
                                    @if ($test->patient_preparation)
                                        <div class="mt-1 text-[10px] text-[#94611A] bg-[#94611A]/5 px-2 py-1 border-l-2 border-[#94611A]">
                                            Preparation: {{ $test->patient_preparation }}
                                        </div>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Section 4: Laboratory Assignment & Routing Preference -->
                <div class="bg-white border border-[#D5DEDF] p-6 space-y-4">
                    <div class="flex items-center gap-2 border-b border-[#D5DEDF] pb-3">
                        <span class="w-6 h-6 rounded-full bg-[#102A36] text-white flex items-center justify-center text-xs font-bold">4</span>
                        <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Laboratory Assignment & Routing Preference <span class="text-red-500">*</span></h2>
                    </div>

                    <div class="space-y-3">
                        <!-- Option 1: Open Network Broadcast (Free request to all qualified labs) -->
                        <label class="block p-4 border transition cursor-pointer"
                               :class="routingMode === 'broadcast' ? 'border-[#167C78] bg-[#E8F0EF]/30 ring-1 ring-[#167C78]' : 'border-[#D5DEDF] hover:border-[#61727D]'">
                            <div class="flex items-start gap-3">
                                <input type="radio" name="routing_mode" value="broadcast" x-model="routingMode" class="mt-0.5 text-[#167C78] focus:ring-[#167C78]">
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#102A36]">Open Network Request (Broadcast to All Qualified Labs)</span>
                                        <span class="px-1.5 py-0.2 bg-blue-100 text-blue-800 text-[10px] font-bold uppercase rounded-sm">First to Accept</span>
                                    </div>
                                    <p class="text-[11px] text-[#61727D] leading-relaxed">
                                        Broadcasts this referral order to all verified, accredited laboratory partners that offer the selected tests. The first qualified laboratory to accept proceeds with sample collection and analysis.
                                    </p>

                                    <!-- Logistics Dispatch Charge Notice for Broadcast -->
                                    <div class="p-3 border rounded-sm text-xs"
                                         :class="collectionMethod === 'courier' ? 'bg-amber-50/60 border-amber-200 text-amber-950' : 'bg-gray-50 border-gray-200 text-[#61727D]'">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold flex items-center gap-1.5">
                                                <svg class="w-4 h-4 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                                Logistics Dispatch Charge:
                                            </span>
                                            <span class="font-mono font-bold"
                                                  x-text="collectionMethod === 'walk_in' ? 'GHS 0.00 (Exempt)' : 'Pending Acceptance (Location-Based)'"></span>
                                        </div>
                                        <p class="text-[11px] mt-1 text-[#61727D]" x-show="collectionMethod === 'courier'">
                                            The dispatch charge will be dynamically set after a particular laboratory accepts the request, calculated based on the fulfilling facility's location & dispatch zone (Intra-Zone: ~GHS 35.00, Inter-Zone: ~GHS 75.00).
                                        </p>
                                        <p class="text-[11px] mt-1 text-[#61727D]" x-show="collectionMethod === 'walk_in'">
                                            Patient Direct Walk-In selected: Courier logistics charge is GHS 0.00 (exempt).
                                        </p>
                                    </div>

                                    <div class="text-[10px] text-[#167C78] flex items-center gap-1.5" x-show="selectedTests.length > 0">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="qualifiedLabs.length > 0 ? 'bg-emerald-600' : 'bg-amber-500'"></span>
                                        <span>Target laboratories offering selected investigations: <strong class="text-[#102A36]" x-text="qualifiedLabs.map(l => l.name).join(', ') || 'No registered lab currently offers all selected investigations'"></strong> (<span x-text="qualifiedLabs.length"></span> fulfilling)</span>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- Option 2: Direct Specific Lab Assignment -->
                        <label class="block p-4 border transition cursor-pointer"
                               :class="routingMode === 'direct' ? 'border-[#167C78] bg-[#E8F0EF]/30 ring-1 ring-[#167C78]' : 'border-[#D5DEDF] hover:border-[#61727D]'">
                            <div class="flex items-start gap-3">
                                <input type="radio" name="routing_mode" value="direct" x-model="routingMode" class="mt-0.5 text-[#167C78] focus:ring-[#167C78]">
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#102A36]">Assign to a Specific Laboratory</span>
                                        <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase rounded-sm">Direct Assignment</span>
                                    </div>
                                    <p class="text-[11px] text-[#61727D] leading-relaxed">
                                        Directly route this referral exclusively to your chosen partner laboratory. Only the designated facility will receive and process the referral.
                                    </p>

                                    <!-- Specific Laboratory Dropdown & Dynamic Dispatch Charge -->
                                    <div x-show="routingMode === 'direct'" class="mt-3 pt-3 border-t border-[#D5DEDF] space-y-3">
                                        <div>
                                            <label class="block text-xs font-bold text-[#102A36] mb-1">Select Designated Laboratory <span class="text-red-500">*</span></label>
                                            <select name="specific_laboratory_id" x-model="specificLabId"
                                                    @change="onSpecificLabChange()"
                                                    class="w-full text-xs border p-2.5 focus:border-[#167C78] focus:outline-none bg-white font-medium"
                                                    :class="!selectedLabQualified && selectedTests.length > 0 ? 'border-amber-400 bg-amber-50/20' : 'border-[#D5DEDF]'">
                                                @forelse ($laboratories as $lab)
                                                    <option value="{{ $lab->id }}">
                                                        {{ $lab->name }} — {{ $lab->city ?? 'Accra' }} ({{ $lab->region?->name ?? 'Greater Accra' }})
                                                        @if ($lab->qualifications->isNotEmpty())
                                                            [{{ $lab->qualifications->pluck('reference_number')->take(2)->join(', ') }}]
                                                        @endif
                                                    </option>
                                                @empty
                                                    <option value="" disabled selected>No accredited laboratories currently verified by Platform Operations</option>
                                                @endforelse
                                            </select>
                                            @if ($laboratories->isEmpty())
                                                <div class="mt-2 p-3 bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-xs flex items-start gap-2">
                                                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                    <div>
                                                        <span class="font-bold">No Verified Laboratories Available:</span> Partner laboratories must have their operating licences and ISO accreditations verified by Platform Operations before they can receive direct referral routing.
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Logistics Dispatch Charge (Follows Selected Lab) -->
                                        <div class="p-3 bg-white border border-[#D5DEDF] space-y-2">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                <div>
                                                    <label class="block text-xs font-bold text-[#102A36]">
                                                        Logistics Dispatch Charge (GHS)
                                                    </label>
                                                    <span class="text-[11px] text-[#61727D]" x-show="collectionMethod === 'courier'">
                                                        Determined by location & courier zone for <strong class="text-[#102A36]" x-text="selectedLabName"></strong>.
                                                    </span>
                                                    <span class="text-[11px] text-[#61727D]" x-show="collectionMethod === 'walk_in'">
                                                        Patient Direct Walk-In selected: Courier logistics charge is GHS 0.00 (exempt).
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="relative w-36">
                                                        <input type="number" step="0.01" min="0"
                                                               x-model="directDispatchCharge"
                                                               :disabled="collectionMethod === 'walk_in'"
                                                               class="w-full text-xs border border-[#D5DEDF] p-2 pr-10 focus:border-[#167C78] focus:outline-none bg-white font-mono font-bold text-[#102A36] disabled:bg-gray-100 disabled:text-gray-400">
                                                        <span class="absolute right-2.5 top-2 text-[#61727D] text-xs font-bold">GHS</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-[10px] text-[#167C78] flex items-center gap-1.5" x-show="collectionMethod === 'courier'">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>Facility Location: <strong class="text-[#102A36]" x-text="currentSelectedLab?.region_name || 'Accra'"></strong> • <span x-text="currentSelectedLab?.is_same_region ? 'Same Service Region (Standard Intra-Zone Courier Rate)' : 'Inter-Regional Cold-Chain Transit Surcharge'"></span></span>
                                            </div>
                                        </div>

                                        <!-- Diagnostic Menu Confirmation Notice -->
                                        <div class="text-[11px]" x-show="selectedTests.length > 0">
                                            <div x-show="selectedLabQualified" class="p-2 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-sm flex items-center gap-2">
                                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                <span><strong>Diagnostic Menu Match:</strong> <span x-text="selectedLabName"></span> offers and fulfills all selected investigations.</span>
                                            </div>
                                            <div x-show="!selectedLabQualified" class="p-2 bg-amber-50 border border-amber-300 text-amber-900 rounded-sm flex items-center gap-2">
                                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                                <span><strong>Menu Notice:</strong> <span x-text="selectedLabName"></span> currently fulfills <span x-text="selectedLabCoverageCount"></span> of <span x-text="selectedTests.length"></span> requested investigations. Qualified partners: <strong class="text-[#102A36]" x-text="qualifiedLabs.map(l => l.name).join(', ') || 'None'"></strong>.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- Option 3: Automated Capacity Matching Engine -->
                        <label class="block p-4 border transition cursor-pointer"
                               :class="routingMode === 'automated' ? 'border-[#167C78] bg-[#E8F0EF]/30 ring-1 ring-[#167C78]' : 'border-[#D5DEDF] hover:border-[#61727D]'">
                            <div class="flex items-start gap-3">
                                <input type="radio" name="routing_mode" value="automated" x-model="routingMode" class="mt-0.5 text-[#167C78] focus:ring-[#167C78]">
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#102A36]">Automated Smart Routing (Algorithm Recommended)</span>
                                        <span class="px-1.5 py-0.2 bg-[#F5F3ED] text-[#102A36] text-[10px] font-bold uppercase rounded-sm border border-[#D5DEDF]">Optimal Match</span>
                                    </div>
                                    <p class="text-[11px] text-[#61727D] leading-relaxed">
                                        NODIVA's matching algorithm immediately scores all qualified laboratories based on turnaround time, regional proximity, courier dispatch cost, equipment capacity, and accreditation to auto-assign the top-ranked facility.
                                    </p>

                                    <!-- Live Algorithm Match & Logistics Dispatch Preview -->
                                    <div x-show="routingMode === 'automated'" class="mt-3 pt-3 border-t border-[#D5DEDF] space-y-3">
                                        <div class="p-3 bg-white border border-[#D5DEDF] space-y-2.5">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#D5DEDF] pb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase rounded-sm">
                                                        Optimal Algorithm Recommendation
                                                    </span>
                                                    <span class="text-xs font-bold text-[#102A36]" x-text="smartRouteMatch ? smartRouteMatch.name : 'Evaluating capacity...'"></span>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs">
                                                    <span class="text-[#61727D]">Logistics Dispatch Charge:</span>
                                                    <span class="font-mono font-bold text-[#102A36]"
                                                          x-text="collectionMethod === 'walk_in' ? 'GHS 0.00 (Exempt)' : 'GHS ' + smartRouteDispatchCharge"></span>
                                                </div>
                                            </div>

                                            <!-- How Automated Smart Routing Works Breakdown -->
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px]">
                                                <div class="p-2 bg-[#F5F3ED] border border-[#D5DEDF] space-y-0.5">
                                                    <div class="font-bold text-[#102A36] text-[10px] uppercase text-[#167C78]">1. Proximity & Dispatch Fee</div>
                                                    <div class="text-[#61727D]" x-text="smartRouteMatch?.is_same_region ? 'Same region (Accra) — lowest courier dispatch cost.' : 'Optimized cross-zone cold-chain courier transport.'"></div>
                                                </div>
                                                <div class="p-2 bg-[#F5F3ED] border border-[#D5DEDF] space-y-0.5">
                                                    <div class="font-bold text-[#102A36] text-[10px] uppercase text-[#167C78]">2. Quality & Reliability</div>
                                                    <div class="text-[#61727D]" x-text="'Reliability score: ' + (smartRouteMatch ? smartRouteMatch.reliability_score + '%' : '98.5%') + ' • Verified HEFRA/AHPC'"></div>
                                                </div>
                                                <div class="p-2 bg-[#F5F3ED] border border-[#D5DEDF] space-y-0.5">
                                                    <div class="font-bold text-[#102A36] text-[10px] uppercase text-[#167C78]">3. Speed & Capacity</div>
                                                    <div class="text-[#61727D]">Prioritizes lowest TAT target and open analyzer batch capacity.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-[10px] text-[#167C78] flex items-center gap-1.5" x-show="selectedTests.length > 0">
                                        <svg class="w-3.5 h-3.5 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        <span>Algorithm will score and balance load across <strong class="text-[#102A36]" x-text="qualifiedLabs.length"></strong> fulfilling accredited laboratory partner(s).</span>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Hidden input to submit the dynamic logistics charge -->
                    <input type="hidden" name="logistics_charge" :value="computedLogisticsCharge">
                </div>
            </div>

            <!-- Right Column: Network Routing & Submit Panel -->
            <div class="space-y-6">
                <!-- Matching Summary Card -->
                <div class="bg-[#102A36] text-white p-6 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#167C78]">Referral Routing & Fulfillment</h3>
                    <p class="text-xs text-white/80 leading-relaxed" x-show="routingMode === 'broadcast'">
                        <strong>Open Network Broadcast:</strong> This order will be dispatched to all verified partner laboratories offering these tests. Whichever laboratory accepts first will claim and fulfill it.
                    </p>
                    <p class="text-xs text-white/80 leading-relaxed" x-show="routingMode === 'direct'">
                        <strong>Direct Laboratory Route:</strong> Order will be routed directly to <span class="font-bold text-[#E8F0EF]" x-text="selectedLabName"></span>.
                    </p>
                    <p class="text-xs text-white/80 leading-relaxed" x-show="routingMode === 'automated'">
                        <strong>Automated Smart Routing:</strong> NODIVA's algorithm evaluates accreditation, regional proximity, turnaround time, and live capacity to route immediately.
                    </p>

                    <div class="space-y-2 pt-2 border-t border-white/10 text-xs">
                        <div class="flex justify-between items-center py-1">
                            <span class="text-white/60">Selected Tests</span>
                            <span class="font-bold text-base text-[#E8F0EF]" x-text="selectedTests.length">0</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-white/60">Routing Mode</span>
                            <span class="font-bold text-[11px] text-right text-[#E8F0EF]"
                                  x-text="routingMode === 'broadcast' ? 'Open Broadcast (First to Accept)' : (routingMode === 'direct' ? 'Direct: ' + selectedLabName : 'Automated Smart Routing')"></span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-white/60">Priority Level</span>
                            <span class="font-bold uppercase text-[11px]" :class="priority === 'urgent' ? 'text-amber-400' : 'text-[#E8F0EF]'" x-text="priority">routine</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-white/60">Fulfillment Mode</span>
                            <span class="font-bold capitalize text-[11px] text-[#E8F0EF]" x-text="collectionMethod">courier</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-white/60">Logistics Dispatch</span>
                            <span class="font-mono font-bold text-[11px] text-right"
                                  :class="collectionMethod === 'walk_in' ? 'text-white/40' : (routingMode === 'broadcast' ? 'text-blue-300' : 'text-amber-300')"
                                  x-text="logisticsChargeSummary"></span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-white/60">Accredited Network</span>
                            <span class="text-emerald-400 font-bold text-[11px]">ISO 15189 / HEFRA</span>
                        </div>
                    </div>

                    <button type="submit" 
                            :disabled="selectedTests.length === 0"
                            :class="selectedTests.length === 0 ? 'opacity-50 cursor-not-allowed bg-[#61727D]' : 'bg-[#167C78] hover:bg-[#136663] text-white'"
                            class="w-full py-3 px-4 text-xs font-bold uppercase tracking-wider transition shadow-sm">
                        Submit Diagnostic Referral
                    </button>
                    <p class="text-[10px] text-white/50 text-center">
                        Barcoded sample custody containers will be registered automatically upon order submission.
                    </p>
                </div>

                <!-- Network Guarantee -->
                <div class="bg-white border border-[#D5DEDF] p-5 space-y-3">
                    <h4 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Clinical Governance Guarantee</h4>
                    <ul class="text-[11px] text-[#61727D] space-y-2">
                        <li class="flex items-start gap-2">
                            <span class="text-[#167C78] font-bold">✓</span>
                            <span>End-to-end barcoded chain of custody from phlebotomy to analyzer bay.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#167C78] font-bold">✓</span>
                            <span>Dual-control result authorization by licensed consultant pathologists.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#167C78] font-bold">✓</span>
                            <span>Instant critical values notification with mandatory acknowledgement.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</x-layouts.workspace>
