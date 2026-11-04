<x-layouts.workspace title="Referral {{ $order->reference }} — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-hospital-sidebar active="dashboard" />
    </x-slot:sidebar>

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold font-mono text-[#102A36]">{{ $order->reference }}</h1>
                <span class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-sm
                    @if ($order->status->value === 'completed') bg-[#247451]/10 text-[#247451] border border-[#247451]/30
                    @elseif (str_contains($order->status->value, 'specimen') || $order->status->value === 'in_transit') bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/30
                    @else bg-[#94611A]/10 text-[#94611A] border border-[#94611A]/30 @endif">
                    {{ $order->status->label() }}
                </span>
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase border border-[#D5DEDF] text-[#61727D]">
                    {{ $order->priority }} Priority
                </span>
            </div>
            <p class="text-xs text-[#61727D] mt-1">
                Requested on {{ $order->created_at->format('M d, Y H:i') }} • Fulfilling Laboratory: <strong class="text-[#102A36]">{{ $order->laboratory?->name ?? 'Matching in progress...' }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3 py-1.5 border border-[#D5DEDF] bg-white text-xs font-semibold text-[#102A36] hover:bg-[#F5F3ED]">
                Print Referral Slip
            </button>
            <a href="{{ route('hospital.dashboard') }}" class="px-3 py-1.5 bg-[#102A36] text-white text-xs font-semibold hover:bg-[#193B49]">
                &larr; Back to Dashboard
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Referral Lifecycle Stepper -->
    <div class="bg-white border border-[#D5DEDF] p-5">
        <h2 class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider mb-4">Diagnostic Lifecycle Stepper</h2>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-2 text-center text-xs">
            @php
                $statusVal = $order->status->value;
                $steps = [
                    ['label' => 'Order Placed', 'active' => true],
                    ['label' => 'Lab Accepted', 'active' => !in_array($statusVal, ['draft', 'matching', 'awaiting_lab_acceptance'])],
                    ['label' => 'Specimen Collected', 'active' => in_array($statusVal, ['specimen_collected', 'in_transit', 'specimen_received', 'processing', 'completed'])],
                    ['label' => 'In Transit', 'active' => in_array($statusVal, ['in_transit', 'specimen_received', 'processing', 'completed'])],
                    ['label' => 'Analytical Processing', 'active' => in_array($statusVal, ['specimen_received', 'processing', 'completed'])],
                    ['label' => 'Results Released', 'active' => $statusVal === 'completed'],
                ];
            @endphp
            @foreach ($steps as $step)
                <div class="p-2 border {{ $step['active'] ? 'bg-[#E8F0EF] border-[#167C78] text-[#167C78] font-bold' : 'bg-[#F5F3ED] border-[#D5DEDF] text-[#61727D]' }}">
                    <div class="text-[10px] font-mono mb-1">{{ $loop->iteration }}</div>
                    <div class="text-[11px]">{{ $step['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Main 2-Col Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Clinical & Result Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Patient Banner -->
            <div class="bg-white border border-[#D5DEDF] p-6 space-y-3">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Patient Identification</h2>
                    <span class="text-[11px] font-mono text-[#61727D]">Ref: {{ $order->patient->reference }}</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <div class="text-[#61727D] text-[10px] uppercase font-bold">Patient Name</div>
                        <div class="font-bold text-[#102A36] mt-0.5">{{ $order->patient->fullName() }}</div>
                    </div>
                    <div>
                        <div class="text-[#61727D] text-[10px] uppercase font-bold">Hospital MRN</div>
                        <div class="font-mono text-[#102A36] mt-0.5">{{ $order->patient->medical_record_number }}</div>
                    </div>
                    <div>
                        <div class="text-[#61727D] text-[10px] uppercase font-bold">Date of Birth / Sex</div>
                        <div class="text-[#102A36] mt-0.5 capitalize">{{ $order->patient->date_of_birth }} ({{ $order->patient->sex }})</div>
                    </div>
                    <div>
                        <div class="text-[#61727D] text-[10px] uppercase font-bold">Contact Phone</div>
                        <div class="text-[#102A36] mt-0.5">{{ $order->patient->phone ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="pt-2 text-xs border-t border-[#D5DEDF]">
                    <span class="text-[#61727D] font-bold">Clinical Indication:</span>
                    <span class="text-[#102A36] ml-1">{{ $order->clinical_indication }}</span>
                </div>
            </div>

            <!-- Requested Tests Table -->
            <div class="bg-white border border-[#D5DEDF]">
                <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
                    <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Requested Diagnostic Tests</h2>
                    <span class="text-xs text-[#61727D]">{{ $order->items->count() }} test(s)</span>
                </div>
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Test Name & Code</th>
                            <th class="px-6 py-3">Specialty</th>
                            <th class="px-6 py-3">Specimen Tube</th>
                            <th class="px-6 py-3 text-right">Turnaround Target</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-6 py-3.5">
                                    <div class="font-bold text-[#102A36]">{{ $item->test->name }}</div>
                                    <div class="font-mono text-[10px] text-[#61727D]">{{ $item->test->code }} (LOINC: {{ $item->test->loinc_code ?? 'N/A' }})</div>
                                </td>
                                <td class="px-6 py-3.5">{{ $item->test->specialty?->name }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 text-[10px] bg-[#E8F0EF] text-[#167C78] font-bold rounded-sm">
                                        {{ $item->test->specimenType?->container ?? 'Standard Tube' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right font-medium">
                                    {{ $item->turnaround_hours ? $item->turnaround_hours . ' hours' : 'Pending Lab' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Diagnostic Results Section (Only visible when officially released by Consultant Pathologist) -->
            @php
                $releasedResults = $order->results->where('status.value', 'released');
            @endphp

            @if ($releasedResults->isNotEmpty())
                <div class="bg-white border border-[#D5DEDF]">
                    <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center bg-[#E8F0EF]/40">
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-700 font-bold">✓</span>
                            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Authorized Diagnostic Results</h2>
                        </div>
                        <span class="text-xs text-[#247451] font-bold">Verified & Released by {{ $releasedResults->first()->releasedBy?->name ?? 'Consultant Pathologist' }}</span>
                    </div>

                    <div class="p-6 space-y-4">
                        @foreach ($releasedResults as $result)
                            <div class="border border-[#D5DEDF] p-4 bg-white">
                                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-2 mb-3">
                                    <div class="font-mono text-xs font-bold text-[#102A36]">Result ID: #{{ $result->id }} (Version {{ $result->version }})</div>
                                    <div class="text-[11px] text-[#61727D]">Released: {{ $result->released_at?->format('M d, Y H:i') ?? 'Authorized' }}</div>
                                </div>

                                <table class="w-full text-left text-xs mb-3">
                                    <thead class="bg-[#F5F3ED] text-[#61727D] uppercase font-semibold">
                                        <tr>
                                            <th class="px-3 py-2">Analyte / Component</th>
                                            <th class="px-3 py-2">Observed Value</th>
                                            <th class="px-3 py-2">Unit</th>
                                            <th class="px-3 py-2">Reference Range</th>
                                            <th class="px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#D5DEDF]">
                                        @foreach ($result->items as $item)
                                            <tr>
                                                <td class="px-3 py-2 font-bold text-[#102A36]">{{ $item->analyte }}</td>
                                                <td class="px-3 py-2 font-bold font-mono">{{ $item->value }}</td>
                                                <td class="px-3 py-2 text-[#61727D]">{{ $item->unit ?? '—' }}</td>
                                                <td class="px-3 py-2 text-[#61727D] font-mono">{{ $item->reference_range ?? 'N/A' }}</td>
                                                <td class="px-3 py-2">
                                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm {{ $item->flag === 'critical' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}">
                                                        {{ $item->flag ?? 'Normal' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                @if ($result->interpretation || $result->review_notes)
                                    <div class="text-xs bg-[#F5F3ED] p-3 border border-[#D5DEDF]">
                                        <div class="font-bold text-[#102A36] text-[11px]">Pathologist Clinical Interpretation:</div>
                                        <p class="text-[#61727D] mt-1">{{ $result->interpretation ?? $result->review_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <!-- Processing Status Box when results are not yet released -->
                <div class="bg-white border border-[#D5DEDF] p-6 space-y-3">
                    <div class="flex items-center gap-2 border-b border-[#D5DEDF] pb-3">
                        <span class="w-2.5 h-2.5 rounded-full {{ $order->status->value === 'awaiting_result_review' ? 'bg-[#94611A]' : 'bg-[#167C78]' }} animate-pulse"></span>
                        <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">
                            {{ $order->status->value === 'awaiting_result_review' ? 'Diagnostic Report in Pathologist Dual-Control Review' : 'Laboratory Analytical Processing' }}
                        </h2>
                    </div>
                    <p class="text-xs text-[#61727D] leading-relaxed">
                        @if ($order->status->value === 'awaiting_result_review')
                            Analytical values have been recorded and are currently undergoing mandatory dual-control verification and clinical authorization by an accredited Consultant Pathologist. Official results will be released here upon pathologist sign-off.
                        @elseif (in_array($order->status->value, ['processing', 'specimen_received']))
                            Specimen has been accessioned at <strong>{{ $order->laboratory?->name ?? 'partner laboratory' }}</strong>. Laboratory analytical processing is currently underway. Internal drafts are strictly withheld until certified and released.
                        @else
                            Diagnostic testing will commence upon specimen transit and laboratory accessioning.
                        @endif
                    </p>
                    <div class="flex flex-wrap items-center gap-4 text-[11px] text-[#61727D] pt-2 border-t border-[#D5DEDF]">
                        <span>Dual-Control Protocol: <strong class="text-[#102A36]">ISO 15189 / AHPC Validated</strong></span>
                        <span>•</span>
                        <span>Authorized Signatory: <strong class="text-[#102A36]">Consultant Pathologist</strong></span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Col: Specimen Barcodes & Custody Log -->
        <div class="space-y-6">
            <!-- Specimen Custody Panel -->
            <div class="bg-white border border-[#D5DEDF] p-5 space-y-4">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-2">
                    <h3 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Specimen Barcodes</h3>
                    <a href="{{ route('hospital.specimens.index') }}" class="text-[11px] text-[#167C78] hover:underline font-bold">Manage &rarr;</a>
                </div>

                <div class="space-y-3">
                    @forelse ($order->specimens as $specimen)
                        <div class="border border-[#D5DEDF] p-3 bg-white space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="font-mono font-bold text-xs text-[#102A36]">{{ $specimen->identifier }}</span>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                    @if ($specimen->status->value === 'received') bg-[#247451]/10 text-[#247451]
                                    @elseif ($specimen->status->value === 'in_transit') bg-[#167C78]/10 text-[#167C78]
                                    @elseif ($specimen->status->value === 'collected') bg-[#94611A]/10 text-[#94611A]
                                    @else bg-gray-100 text-gray-700 @endif">
                                    {{ $specimen->status->label() }}
                                </span>
                            </div>
                            <div class="text-[11px] text-[#61727D]">
                                Container: <strong>{{ $specimen->type?->container }}</strong> ({{ $specimen->type?->transport_requirements }})
                            </div>

                            @if ($specimen->status->value === 'pending_collection')
                                <form action="{{ route('hospital.specimens.collect', $specimen) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full mt-1 py-1.5 px-3 bg-[#167C78] hover:bg-[#136663] text-white text-[11px] font-bold uppercase transition">
                                        Record Phlebotomy Collection
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="text-xs text-[#61727D] py-3 text-center">
                            No specimen containers assigned yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Logistics & Transport Summary -->
            <div class="bg-white border border-[#D5DEDF] p-5 space-y-3">
                <h3 class="text-xs font-bold text-[#102A36] uppercase tracking-wider border-b border-[#D5DEDF] pb-2">Logistics & Transport</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-[#61727D]">Collection Method:</span>
                        <span class="font-bold text-[#102A36]">
                            {{ $order->collection_method === 'courier' ? 'Hospital Phlebotomy (Courier Pickup)' : 'Patient Direct Walk-In' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#61727D]">Logistics Dispatch Charge:</span>
                        <span class="font-mono font-bold text-[#102A36]">
                            @if ($order->collection_method === 'walk_in')
                                GHS 0.00 (Exempt)
                            @elseif ($order->logistics_charge !== null)
                                GHS {{ number_format((float) $order->logistics_charge, 2) }}
                            @elseif (! $order->laboratory_id)
                                <span class="text-blue-600 font-sans font-semibold text-[11px]">Pending Acceptance (Location-Based)</span>
                            @else
                                GHS 35.00
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#61727D]">Routing Mode:</span>
                        <span class="font-bold capitalize text-[#102A36]">
                            {{ $order->selection_mode ? str_replace('_', ' ', $order->selection_mode) : 'Open Broadcast' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Fulfilling Partner Profile -->
            <div class="bg-white border border-[#D5DEDF] p-5 space-y-3">
                <h3 class="text-xs font-bold text-[#102A36] uppercase tracking-wider border-b border-[#D5DEDF] pb-2">Assigned Diagnostic Facility</h3>
                @if ($order->laboratory)
                    <div>
                        <div class="font-bold text-xs text-[#102A36]">{{ $order->laboratory->name }}</div>
                        <div class="text-[11px] text-[#61727D] mt-0.5">{{ $order->laboratory->city }}, {{ $order->laboratory->region?->name ?? 'Ghana' }}</div>
                    </div>
                    <div class="text-[11px] text-[#61727D] space-y-1 pt-2 border-t border-[#D5DEDF]">
                        <div>Primary Contact: <strong class="text-[#102A36]">{{ $order->laboratory->phone ?? '+233 30 200 0003' }}</strong></div>
                        <div>Email: <strong class="text-[#102A36]">{{ $order->laboratory->email }}</strong></div>
                        <div class="flex items-center gap-1.5 text-emerald-700 font-bold mt-1">
                            <span>✓</span>
                            <span>Licence Verified: HEFRA / ISO 15189</span>
                        </div>
                    </div>
                @else
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-bold uppercase bg-blue-100 text-blue-800 rounded-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                            Open Network Broadcast (Awaiting Lab Acceptance)
                        </div>
                        <p class="text-[11px] text-[#61727D] leading-relaxed">
                            This referral has been broadcast to all qualified accredited partner laboratories. The first qualified laboratory to accept will claim and fulfill the referral.
                        </p>
                    </div>
                @endif
            </div>

            <!-- Status Transition History -->
            <div class="bg-white border border-[#D5DEDF] p-5 space-y-3">
                <h3 class="text-xs font-bold text-[#102A36] uppercase tracking-wider border-b border-[#D5DEDF] pb-2">Audit Status Events</h3>
                <div class="space-y-3 max-h-56 overflow-y-auto text-xs">
                    @foreach ($order->events as $event)
                        <div class="text-[11px] border-l-2 border-[#167C78] pl-2.5 space-y-0.5">
                            <div class="font-bold text-[#102A36]">{{ $event->to_status }}</div>
                            <div class="text-[#61727D]">{{ $event->notes ?? 'Status transitioned' }}</div>
                            <div class="text-[10px] text-[#61727D]/80">{{ $event->created_at->format('M d, H:i') }} • {{ $event->actor?->name ?? 'System' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-layouts.workspace>
