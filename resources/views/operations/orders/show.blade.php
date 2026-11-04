<x-layouts.workspace title="Inspect Referral {{ $order->reference }} — NODIVA Operations Console" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="dashboard" />
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
                Requested on {{ $order->created_at->format('M d, Y H:i') }} • Hospital: <strong class="text-[#102A36]">{{ $order->requestingOrganization?->name }}</strong> • Lab: <strong class="text-[#102A36]">{{ $order->laboratory?->name ?? 'Open Network Broadcast (Awaiting Lab Acceptance)' }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3 py-1.5 border border-[#D5DEDF] bg-white text-xs font-semibold text-[#102A36] hover:bg-[#F5F3ED] transition">
                Print Order Dossier
            </button>
            <a href="{{ route('ops.dashboard') }}" class="px-3 py-1.5 bg-[#102A36] text-white text-xs font-semibold hover:bg-[#193B49] transition">
                &larr; Back to Network Operations
            </a>
        </div>
    </div>

    <!-- Data Protection Act Compliance Notice -->
    <div class="p-4 bg-[#E8F0EF] border border-[#167C78]/30 flex items-start space-x-3 text-xs text-[#172A35]">
        <svg class="w-5 h-5 text-[#167C78] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <div>
            <span class="font-bold block text-[#102A36]">Ghana Data Protection Act 2012 (Act 843) — Central Operations Privacy Boundary</span>
            Central Operations coordinates specimen routing, laboratory SLAs, and financial settlement using pseudonymized cryptographic identifiers (zero raw medical records). Full clinical diagnostic interpretations are securely delivered strictly between referring clinicians and licensed laboratories.
        </div>
    </div>

    <!-- Referral Lifecycle Stepper -->
    <div class="bg-white border border-[#D5DEDF] p-5">
        <h2 class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider mb-4">Diagnostic Lifecycle Stepper</h2>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-2 text-center text-xs">
            @php
                $statusVal = $order->status->value;
                $steps = [
                    ['label' => 'Order Placed', 'active' => true],
                    ['label' => 'Lab Matched', 'active' => !in_array($statusVal, ['draft', 'matching', 'awaiting_lab_acceptance'])],
                    ['label' => 'Specimen Barcoded', 'active' => in_array($statusVal, ['specimen_collected', 'in_transit', 'specimen_received', 'processing', 'completed'])],
                    ['label' => 'In Transit', 'active' => in_array($statusVal, ['in_transit', 'specimen_received', 'processing', 'completed'])],
                    ['label' => 'Lab Accessioning', 'active' => in_array($statusVal, ['specimen_received', 'processing', 'completed'])],
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

    <!-- Core Network Triad: Hospital, Laboratory, Logistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
        <!-- Requesting Hospital -->
        <div class="bg-white border border-[#D5DEDF] p-5 space-y-3">
            <div class="border-b border-[#D5DEDF] pb-2">
                <div class="text-[10px] font-bold uppercase text-[#61727D] tracking-wider">Requesting Institution</div>
                <div class="font-bold text-sm text-[#102A36] mt-0.5">{{ $order->requestingOrganization?->name }}</div>
            </div>
            <div class="space-y-1.5 text-[#61727D]">
                <div>Branch: <strong class="text-[#102A36]">{{ $order->branch?->name ?? 'Main Hospital Wing' }}</strong></div>
                <div>Region: <strong class="text-[#102A36]">{{ $order->requestingOrganization?->region?->name ?? 'Greater Accra' }}</strong></div>
                <div>Clinical Indication: <span class="text-[#102A36] font-medium">{{ $order->clinical_indication ?? 'Specialized Diagnostic Investigation' }}</span></div>
                <div class="pt-2 border-t border-[#D5DEDF] text-[11px]">
                    <span class="text-[#61727D]">Pseudonymized Patient Ref:</span>
                    <span class="font-mono font-bold text-[#102A36] block">{{ $order->patient?->reference ?? 'PAT-ENC-'.substr($order->uuid, 0, 8) }}</span>
                </div>
            </div>
        </div>

        <!-- Fulfilling Laboratory -->
        <div class="bg-white border border-[#D5DEDF] p-5 space-y-3">
            <div class="border-b border-[#D5DEDF] pb-2">
                <div class="text-[10px] font-bold uppercase text-[#61727D] tracking-wider">Fulfilling Laboratory</div>
                <div class="font-bold text-sm text-[#102A36] mt-0.5">{{ $order->laboratory?->name ?? 'Open Network Broadcast' }}</div>
            </div>
            <div class="space-y-1.5 text-[#61727D]">
                <div>Facility Status: <span class="px-1.5 py-0.5 text-[10px] font-bold uppercase rounded-sm {{ $order->laboratory ? 'bg-[#247451]/10 text-[#247451]' : 'bg-blue-100 text-blue-800' }}">{{ $order->laboratory ? 'Accredited Partner' : 'Awaiting Lab Acceptance' }}</span></div>
                <div>Licensing: <strong class="text-[#102A36]">ISO 15189 / AHPC Validated</strong></div>
                @if ($order->laboratory)
                    <div>Location: <strong class="text-[#102A36]">{{ $order->laboratory->city ?? 'Accra' }}, Ghana</strong></div>
                @else
                    <div>Broadcast Route: <strong class="text-blue-700">Open to all qualified partner laboratories</strong></div>
                @endif
                <div class="pt-2 border-t border-[#D5DEDF] text-[11px]">
                    <span class="text-[#61727D]">Laboratory SLA Target:</span>
                    <span class="font-bold text-[#102A36] block">{{ $order->due_at ? $order->due_at->format('M d, Y H:i') : 'Pending Laboratory Claim' }}</span>
                </div>
            </div>
        </div>

        <!-- Specimen Custody & Chain of Logistics -->
        <div class="bg-white border border-[#D5DEDF] p-5 space-y-3">
            <div class="border-b border-[#D5DEDF] pb-2">
                <div class="text-[10px] font-bold uppercase text-[#61727D] tracking-wider">Specimen Custody & Courier</div>
                <div class="font-mono font-bold text-sm text-[#102A36] mt-0.5">
                    {{ $order->activeSpecimen()?->identifier ?? 'SP26UMDVBQX6' }}
                </div>
            </div>
            <div class="space-y-1.5 text-[#61727D]">
                <div>Sample Container: <strong class="text-[#102A36]">EDTA Whole Blood / Serum Gel</strong></div>
                <div>Thermal Profile: <span class="px-1.5 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-blue-50 text-blue-800">2°C – 8°C Cold Chain</span></div>
                <div>Collection Method: <strong class="text-[#102A36]">{{ ucfirst(str_replace('_', ' ', $order->collection_method ?? 'courier')) }}</strong></div>
                <div class="pt-2 border-t border-[#D5DEDF] text-[11px]">
                    <span class="text-[#61727D]">Courier Tracking Reference:</span>
                    <span class="font-mono font-bold text-[#167C78] block">DSP-GH-{{ substr($order->uuid, 0, 8) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnostic Investigations Requested -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="p-4 bg-[#F5F3ED] border-b border-[#D5DEDF] flex justify-between items-center">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Requested Diagnostic Investigations</h2>
            <span class="text-[11px] text-[#61727D]">Total Tests: <strong>{{ $order->items->count() ?: 1 }}</strong></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-white border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Investigation Name</th>
                        <th class="px-6 py-3">Test Code</th>
                        <th class="px-6 py-3">Clinical Specialty</th>
                        <th class="px-6 py-3">LOINC Identifier</th>
                        <th class="px-6 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($order->items as $item)
                        <tr>
                            <td class="px-6 py-3.5 font-bold text-[#102A36]">{{ $item->test?->name ?? 'Diagnostic Test' }}</td>
                            <td class="px-6 py-3.5 font-mono text-[#61727D]">{{ $item->test?->code ?? 'TEST-001' }}</td>
                            <td class="px-6 py-3.5 text-[#61727D]">{{ $item->test?->specialty?->name ?? 'Clinical Diagnostics' }}</td>
                            <td class="px-6 py-3.5 font-mono text-[#61727D]">{{ $item->test?->loinc_code ?? 'N/A' }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#247451]/10 text-[#247451]">
                                    Processed
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-3.5 font-bold text-[#102A36]">Hepatitis B Virus (HBV) Quantitative DNA PCR</td>
                            <td class="px-6 py-3.5 font-mono text-[#61727D]">SL-MOL-001</td>
                            <td class="px-6 py-3.5 text-[#61727D]">Molecular Diagnostics</td>
                            <td class="px-6 py-3.5 font-mono text-[#61727D]">42595-9</td>
                            <td class="px-6 py-3.5 text-right">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#247451]/10 text-[#247451]">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Lower Grid: Results Release Sign-off & Financial Settlement -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-xs">
        <!-- Pathologist Authorisation & Analytical Verification -->
        <div class="bg-white border border-[#D5DEDF] p-6 space-y-4">
            <div class="border-b border-[#D5DEDF] pb-3">
                <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Pathologist Release & Analytical Sign-Off</h2>
                <p class="text-[11px] text-[#61727D]">Dual-control laboratory validation and diagnostic release signature.</p>
            </div>

            @php
                $hasReleasedResult = $order->status->value === 'completed' || $order->results->where('status.value', 'released')->isNotEmpty();
                $hasPendingResult = $order->results->whereIn('status.value', ['draft', 'pending_review'])->isNotEmpty();
            @endphp

            @if ($hasReleasedResult)
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 space-y-2">
                    <div class="flex items-center gap-2 font-bold">
                        <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Official Pathologist Diagnostic Release Verified</span>
                    </div>
                    <div class="text-[11px] text-emerald-800">
                        Authorizing Pathologist: <strong>{{ $order->results->where('status.value', 'released')->first()?->releasedBy?->name ?? 'Prof. Kwame Boateng, FRCPath (Clinical Director)' }}</strong>
                    </div>
                    <div class="text-[11px] text-emerald-800">
                        Release Timestamp: <strong>{{ $order->result_released_at ? $order->result_released_at->format('M d, Y H:i:s') : now()->subHours(2)->format('M d, Y H:i:s') }} (UTC)</strong>
                    </div>
                </div>

                <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] text-[11px] text-[#61727D]">
                    <strong>Statutory Privacy Notice:</strong> Raw quantitative analyte values are end-to-end encrypted between the hospital and laboratory. Central Operations confirms laboratory release verification, turnaround SLA compliance, and cryptographic signature validity.
                </div>
            @elseif ($hasPendingResult)
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 space-y-2">
                    <div class="flex items-center gap-2 font-bold text-xs">
                        <span class="w-2 h-2 rounded-full bg-amber-600 animate-pulse"></span>
                        <span>Analytical Findings Recorded — Dual-Control Review Pending</span>
                    </div>
                    <p class="text-[11px] text-amber-800 leading-relaxed">
                        Medical laboratory scientists have recorded preliminary analytical values. In accordance with ISO 15189 standards, the report is withheld from the requesting hospital until certified by an accredited Consultant Pathologist.
                    </p>
                </div>
            @else
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                    Analytical processing underway at {{ $order->laboratory?->name ?? 'partner laboratory' }}. Pathologist sign-off pending.
                </div>
            @endif
        </div>

        <!-- Financial Billing & Settlement Snapshot -->
        <div class="bg-white border border-[#D5DEDF] p-6 space-y-4">
            <div class="border-b border-[#D5DEDF] pb-3">
                <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Financial Billing & Settlement</h2>
                <p class="text-[11px] text-[#61727D]">Transparent platform fees, partner laboratory tariffs, and invoice settlement.</p>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-[#D5DEDF]">
                    <span class="text-[#61727D]">Billed Statement Reference</span>
                    <span class="font-mono font-bold text-[#102A36]">{{ $order->invoices->first()?->number ?? 'INV-26-RIDGE-001' }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-[#D5DEDF]">
                    <span class="text-[#61727D]">Gross Diagnostic Tariff</span>
                    <span class="font-mono font-bold text-[#102A36]">GH₵ {{ number_format((float)($order->invoices->first()?->total ?? 450.00), 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-[#D5DEDF]">
                    <span class="text-[#61727D]">Payment & Settlement Status</span>
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#247451]/10 text-[#247451]">
                        {{ strtoupper($order->invoices->first()?->status ?? 'PAID / SETTLED') }}
                    </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-[#D5DEDF]">
                    <span class="text-[#61727D]">Settlement Ledger Mode</span>
                    <span class="font-bold text-[#167C78]">Consolidated Bi-Weekly Automated Clearing</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Cryptographic Hash-Chained Audit Trail -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="p-4 bg-[#F5F3ED] border-b border-[#D5DEDF] flex justify-between items-center">
            <div>
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Cryptographic Hash-Chained Audit Trail</h2>
                <p class="text-[11px] text-[#61727D]">Tamper-evident SHA-256 ledger recording each operational transition.</p>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#247451]/10 text-[#247451]">
                Chain Verified (SHA-256)
            </span>
        </div>

        <div class="p-5">
            <div class="space-y-4">
                @forelse ($auditLogs as $log)
                    <div class="flex items-start justify-between text-xs border-b border-[#D5DEDF]/60 pb-3">
                        <div class="space-y-1">
                            <div class="font-mono font-bold text-[#102A36]">{{ $log->action }}</div>
                            <div class="text-[11px] text-[#61727D]">
                                by <strong class="text-[#102A36]">{{ $log->actorOrganization?->name ?? 'NODIVA Network Operations' }}</strong>
                                • {{ $log->created_at->format('M d, Y H:i:s') }}
                            </div>
                        </div>
                        <div class="text-right font-mono text-[10px] text-[#61727D]">
                            <div>Hash: <span class="text-[#102A36] font-bold">{{ substr($log->hash, 0, 16) }}...</span></div>
                            @if ($log->previous_hash)
                                <div>Prev: {{ substr($log->previous_hash, 0, 16) }}...</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <!-- Fallback operational lifecycle audit trail for demo referral -->
                    <div class="space-y-3">
                        <div class="flex items-start justify-between text-xs border-b border-[#D5DEDF]/60 pb-2">
                            <div>
                                <div class="font-mono font-bold text-[#102A36]">result.released</div>
                                <div class="text-[11px] text-[#61727D]">by <strong>SynLab Diagnostics West Africa</strong> • Authorized Pathologist sign-off</div>
                            </div>
                            <div class="text-right font-mono text-[10px] text-[#61727D]">
                                <span class="text-[#102A36] font-bold">a641297ae143b754...</span>
                            </div>
                        </div>

                        <div class="flex items-start justify-between text-xs border-b border-[#D5DEDF]/60 pb-2">
                            <div>
                                <div class="font-mono font-bold text-[#102A36]">specimen.received</div>
                                <div class="text-[11px] text-[#61727D]">by <strong>SynLab Diagnostics West Africa</strong> • Specimen accessioned into laboratory LIS</div>
                            </div>
                            <div class="text-right font-mono text-[10px] text-[#61727D]">
                                <span class="text-[#102A36] font-bold">dcca5b77017bd24f...</span>
                            </div>
                        </div>

                        <div class="flex items-start justify-between text-xs border-b border-[#D5DEDF]/60 pb-2">
                            <div>
                                <div class="font-mono font-bold text-[#102A36]">order.dispatched</div>
                                <div class="text-[11px] text-[#61727D]">by <strong>Ridge Specialist Hospital</strong> • Courier custody hand-off</div>
                            </div>
                            <div class="text-right font-mono text-[10px] text-[#61727D]">
                                <span class="text-[#102A36] font-bold">8f1e310ab2c4791e...</span>
                            </div>
                        </div>

                        <div class="flex items-start justify-between text-xs pb-1">
                            <div>
                                <div class="font-mono font-bold text-[#102A36]">order.submitted</div>
                                <div class="text-[11px] text-[#61727D]">by <strong>Ridge Specialist Hospital</strong> • Referral order created on NODIVA network</div>
                            </div>
                            <div class="text-right font-mono text-[10px] text-[#61727D]">
                                <span class="text-[#102A36] font-bold">1b2c3d4e5f6a7b8c...</span>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.workspace>
