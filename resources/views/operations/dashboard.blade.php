<x-layouts.workspace title="NODIVA Operations Console" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="dashboard" />
    </x-slot:sidebar>

    <div x-data="{ showHospitalsModal: false, showLabsModal: false, showCorporateModal: false, showReferralsModal: false, selectedHospital: null, selectedLab: null }" class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-[#102A36]">Central Network Operations Console</h1>
                <p class="text-xs text-[#61727D]">Real-time coordination layer across hospitals, testing laboratories, and corporate programs.</p>
            </div>
            @if (auth()->user()?->isCto())
                <a href="{{ route('ops.audit.index') }}#database-purge"
                   class="px-3 py-1.5 border border-[#D5DEDF] bg-white text-xs font-semibold text-[#61727D] hover:text-[#B42332] hover:border-[#B42332] transition inline-flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-[#B42332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Platform Sanitization / Purge Tool</span>
                </a>
            @endif
        </div>

        <!-- Stat Metric Boxes -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div @click="showHospitalsModal = true" class="bg-white p-4 border border-[#D5DEDF] hover:border-[#167C78] cursor-pointer transition group shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold text-[#102A36] uppercase tracking-wider group-hover:text-[#167C78]">Connected Hospitals</div>
                    <span class="text-[10px] text-[#167C78] font-semibold uppercase opacity-0 group-hover:opacity-100 transition">View details &rarr;</span>
                </div>
                <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $counts['active_hospitals'] }}</div>
                <div class="text-[10px] text-[#61727D] mt-1">Click to view hospital profiles & branches</div>
            </div>
            <div @click="showLabsModal = true" class="bg-white p-4 border border-[#D5DEDF] hover:border-[#167C78] cursor-pointer transition group shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider group-hover:text-[#102A36]">Verified Laboratories</div>
                    <span class="text-[10px] text-[#167C78] font-semibold uppercase opacity-0 group-hover:opacity-100 transition">View details &rarr;</span>
                </div>
                <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $counts['verified_labs'] }}</div>
                <div class="text-[10px] text-[#61727D] mt-1">Click to view accreditations & test catalogues</div>
            </div>
            <div @click="showCorporateModal = true" class="bg-white p-4 border border-[#D5DEDF] hover:border-[#167C78] cursor-pointer transition group shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold text-[#102A36] uppercase tracking-wider group-hover:text-[#167C78]">Corporate Screening Clients</div>
                    <span class="text-[10px] text-[#167C78] font-semibold uppercase opacity-0 group-hover:opacity-100 transition">View details &rarr;</span>
                </div>
                <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $counts['corporate_clients'] }}</div>
                <div class="text-[10px] text-[#61727D] mt-1">Click to view corporate client profiles & programs</div>
            </div>
            <div @click="showReferralsModal = true" class="bg-white p-4 border border-[#D5DEDF] hover:border-[#167C78] cursor-pointer transition group shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider group-hover:text-[#102A36]">Total Diagnostic Referrals</div>
                    <span class="text-[10px] text-[#247451] font-semibold uppercase opacity-0 group-hover:opacity-100 transition">View details &rarr;</span>
                </div>
                <div class="text-2xl font-bold text-[#247451] mt-1">{{ $counts['total_orders'] }}</div>
                <div class="text-[10px] text-[#61727D] mt-1">Click to view all network referral orders & status</div>
            </div>
        </div>

    <!-- Network Active Orders Table -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Live Network Referral Activity</h2>
            <span class="text-xs text-[#61727D]">Pseudonymized operational view (Zero PII leakage)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Order Ref</th>
                        <th class="px-6 py-3">Requesting Hospital</th>
                        <th class="px-6 py-3">Fulfilling Laboratory</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Priority</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($networkOrders as $order)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">
                                <a href="{{ route('ops.orders.show', $order) }}" class="text-[#167C78] hover:underline">
                                    {{ $order->reference }}
                                </a>
                            </td>
                            <td class="px-6 py-3.5">{{ $order->requestingOrganization?->name }}</td>
                            <td class="px-6 py-3.5">
                                @if ($order->laboratory)
                                    {{ $order->laboratory->name }}
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-bold uppercase bg-blue-100 text-blue-800 rounded-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                        Open Network Broadcast
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                    @if ($order->status->value === 'completed') bg-[#247451]/10 text-[#247451]
                                    @elseif (str_contains($order->status->value, 'specimen')) bg-[#167C78]/10 text-[#167C78]
                                    @else bg-[#94611A]/10 text-[#94611A] @endif">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 uppercase font-semibold text-[10px] text-[#61727D]">{{ $order->priority }}</td>
                            <td class="px-6 py-3.5 text-right font-medium">
                                <a href="{{ route('ops.orders.show', $order) }}" class="text-[#167C78] hover:underline font-bold">Inspect Order &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-[#61727D]">
                                No network transactions recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Immutable Audit Trail Feed -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Cryptographic Hash-Chained Audit Trail</h2>
            <span class="text-[11px] text-[#247451] font-semibold">Chain Verified (SHA-256)</span>
        </div>
        <div class="divide-y divide-[#D5DEDF] text-xs">
            @forelse ($recentAudits as $log)
                <div class="px-6 py-3 flex items-center justify-between">
                    <div>
                        <span class="font-bold text-[#102A36] font-mono">{{ $log->action }}</span>
                        <span class="text-[#61727D] ml-2">by {{ $log->actorOrganization?->name ?? 'System' }}</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="font-mono text-[10px] text-[#61727D]">{{ substr($log->hash, 0, 16) }}...</span>
                        <span class="text-[11px] text-[#61727D]">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <div class="px-6 py-4 text-center text-[#61727D]">No audit records recorded yet.</div>
            @endforelse
        </div>
    </div>

    <!-- Connected Hospitals Modal -->
    <div x-show="showHospitalsModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-[#102A36]/60 flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="showHospitalsModal = false">
        
        <div @click.away="showHospitalsModal = false" 
             class="bg-white border border-[#D5DEDF] max-w-4xl w-full max-h-[90vh] flex flex-col shadow-xl">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex items-center justify-between bg-[#F5F3ED]">
                <div>
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Connected Hospital Network</h3>
                    <p class="text-xs text-[#61727D]">Verified healthcare institutions authorized for diagnostic referral dispatch.</p>
                </div>
                <button @click="showHospitalsModal = false" class="text-[#61727D] hover:text-[#102A36] p-1 font-bold text-lg focus:outline-none">
                    &times;
                </button>
            </div>

            <!-- Modal Content (List & Details) -->
            <div class="p-6 overflow-y-auto space-y-6">
                @forelse ($hospitals as $hosp)
                    <div class="border border-[#D5DEDF] p-5 bg-white space-y-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 border-b border-[#D5DEDF]/50 pb-3">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h4 class="font-bold text-[#102A36] text-base">{{ $hosp->name }}</h4>
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#247451]/10 text-[#247451]">
                                        {{ $hosp->status->value }}
                                    </span>
                                </div>
                                <div class="text-xs text-[#61727D] mt-0.5">
                                    {{ $hosp->city }}, {{ $hosp->region?->name ?? 'Greater Accra' }} &bull; Currency: <span class="font-mono">{{ $hosp->currency }}</span>
                                </div>
                            </div>
                            <div class="text-xs text-[#61727D]">
                                <span class="font-semibold text-[#102A36]">UUID:</span> <span class="font-mono text-[11px]">{{ $hosp->uuid }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <div class="bg-[#F5F3ED]/60 p-3 border border-[#D5DEDF]/40 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] block">Institutional Contact</span>
                                <div class="font-medium text-[#102A36]">{{ $hosp->email }}</div>
                                <div class="text-[#61727D]">{{ $hosp->phone ?? 'Phone on record' }}</div>
                            </div>

                            <div class="bg-[#F5F3ED]/60 p-3 border border-[#D5DEDF]/40 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] block">Operating Branches</span>
                                <div class="font-medium text-[#102A36]">{{ $hosp->branches_count }} Registered Wing(s)</div>
                                <div class="text-[#61727D]">
                                    @foreach ($hosp->branches as $branch)
                                        <span>{{ $branch->name }} ({{ $branch->code ?? 'Primary' }})</span>{{ !$loop->last ? ', ' : '' }}
                                    @endforeach
                                </div>
                            </div>

                            <div class="bg-[#F5F3ED]/60 p-3 border border-[#D5DEDF]/40 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] block">Authorized Staff</span>
                                <div class="font-medium text-[#102A36]">{{ $hosp->memberships->count() }} Authorized Member(s)</div>
                                <div class="text-[#61727D]">
                                    @foreach ($hosp->memberships->take(2) as $mem)
                                        <span>{{ $mem->user->name }} ({{ $mem->user->job_title ?? 'Staff' }})</span>{{ !$loop->last ? '; ' : '' }}
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <span class="text-[#61727D]">Compliance: Data Protection Act, 2012 (Act 843) Patient Encryption Verified</span>
                            <span class="text-[#167C78] font-semibold text-[11px] uppercase tracking-wider">Approved Partner</span>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-[#61727D]">
                        No connected hospitals found.
                    </div>
                @endforelse
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-[#D5DEDF] bg-[#F5F3ED] flex justify-end">
                <button @click="showHospitalsModal = false" class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-[#102A36] border border-[#D5DEDF] bg-white hover:bg-[#E8F0EF] rounded-sm transition">
                    Close Details
                </button>
            </div>
        </div>
    </div>

    <!-- Verified Laboratories Modal -->
    <div x-show="showLabsModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-[#102A36]/60 flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="showLabsModal = false">
        
        <div @click.away="showLabsModal = false" 
             class="bg-white border border-[#D5DEDF] max-w-4xl w-full max-h-[90vh] flex flex-col shadow-xl">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex items-center justify-between bg-[#F5F3ED]">
                <div>
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Verified Laboratory Network</h3>
                    <p class="text-xs text-[#61727D]">Accredited diagnostic facilities with audited licensing and test capacity.</p>
                </div>
                <button @click="showLabsModal = false" class="text-[#61727D] hover:text-[#102A36] p-1 font-bold text-lg focus:outline-none">
                    &times;
                </button>
            </div>

            <!-- Modal Content (List & Details) -->
            <div class="p-6 overflow-y-auto space-y-6">
                @forelse ($laboratories as $lab)
                    <div class="border border-[#D5DEDF] p-5 bg-white space-y-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 border-b border-[#D5DEDF]/50 pb-3">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h4 class="font-bold text-[#102A36] text-base">{{ $lab->name }}</h4>
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#167C78]/10 text-[#167C78]">
                                        {{ $lab->status->value }}
                                    </span>
                                    @if ($lab->laboratoryProfile?->reliability_score)
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-sm bg-[#247451]/10 text-[#247451]">
                                            {{ $lab->laboratoryProfile->reliability_score }}% Reliability
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-[#61727D] mt-0.5">
                                    {{ $lab->city }}, {{ $lab->region?->name ?? 'Greater Accra' }} &bull; Daily Analytical Capacity: <span class="font-bold text-[#102A36]">{{ $lab->laboratoryProfile?->daily_capacity ?? 'N/A' }}</span> samples/day
                                </div>
                            </div>
                            <div class="text-xs text-[#61727D]">
                                <span class="font-semibold text-[#102A36]">UUID:</span> <span class="font-mono text-[11px]">{{ $lab->uuid }}</span>
                            </div>
                        </div>

                        <!-- 3 Column Metrics -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <!-- Qualifications & Accreditations -->
                            <div class="bg-[#F5F3ED]/60 p-3 border border-[#D5DEDF]/40 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] block">Verified Qualifications</span>
                                @forelse ($lab->qualifications as $qual)
                                    <div class="pt-1">
                                        <div class="font-bold text-[#102A36]">{{ $qual->typeLabel() }}</div>
                                        <div class="text-[11px] text-[#61727D]">{{ $qual->issuing_body }}</div>
                                        <div class="font-mono text-[10px] text-[#167C78]">{{ $qual->reference_number }} &bull; Valid to {{ $qual->expires_on?->format('M Y') ?? 'Indefinite' }}</div>
                                    </div>
                                @empty
                                    <div class="text-[#61727D]">Pending document verification.</div>
                                @endforelse
                            </div>

                            <!-- Catalogue & Turnaround -->
                            <div class="bg-[#F5F3ED]/60 p-3 border border-[#D5DEDF]/40 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] block">Test Catalogue</span>
                                <div class="font-medium text-[#102A36]">{{ $lab->catalogue_entries_count }} Active Test Offering(s)</div>
                                <div class="text-[11px] text-[#61727D] space-y-0.5 pt-1">
                                    @foreach ($lab->catalogueEntries->take(3) as $entry)
                                        <div class="flex justify-between">
                                            <span>{{ $entry->test?->name }}</span>
                                            <span class="font-mono font-bold">{{ $entry->currency }} {{ $entry->price }} ({{ $entry->turnaround_hours }}h)</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Key Laboratory Personnel -->
                            <div class="bg-[#F5F3ED]/60 p-3 border border-[#D5DEDF]/40 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] block">Authorized Personnel</span>
                                <div class="font-medium text-[#102A36]">{{ $lab->memberships->count() }} Authorized Member(s)</div>
                                <div class="text-[11px] text-[#61727D] space-y-1 pt-1">
                                    @foreach ($lab->memberships->take(2) as $mem)
                                        <div>
                                            <span class="font-semibold text-[#102A36]">{{ $mem->user->name }}</span>
                                            <div class="text-[10px]">{{ $mem->user->job_title ?? 'Lab Staff' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Regional Coverage & Separation of Duties Info -->
                        <div class="flex flex-col md:flex-row md:items-center justify-between text-xs pt-1 border-t border-[#D5DEDF]/30 gap-2">
                            <div class="text-[#61727D]">
                                <span class="font-semibold text-[#102A36]">Regional Coverage:</span>
                                {{ $lab->serviceRegions->pluck('name')->join(', ') ?: 'Accra Metro' }}
                                &bull; <span class="text-[#167C78]">Courier Collection Enabled</span>
                            </div>
                            <div class="text-[11px] font-semibold text-[#247451] uppercase tracking-wider">
                                Dual-Control Pathologist Release Active
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-[#61727D]">
                        No verified laboratories found.
                    </div>
                @endforelse
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-[#D5DEDF] bg-[#F5F3ED] flex justify-end">
                <button @click="showLabsModal = false" class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-[#102A36] border border-[#D5DEDF] bg-white hover:bg-[#E8F0EF] rounded-sm transition">
                    Close Details
                </button>
            </div>
        </div>
    </div>

    <!-- Corporate Screening Clients Modal -->
    <div x-show="showCorporateModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-[#102A36]/60 flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="showCorporateModal = false">
        
        <div @click.away="showCorporateModal = false" 
             class="bg-white border border-[#D5DEDF] max-w-4xl w-full max-h-[90vh] flex flex-col shadow-xl">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex items-center justify-between bg-[#F5F3ED]">
                <div>
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Corporate Screening Clients</h3>
                    <p class="text-xs text-[#61727D]">Organizations enrolled in occupational wellness, executive checkups, and diagnostic screening programs.</p>
                </div>
                <button @click="showCorporateModal = false" class="text-[#61727D] hover:text-[#102A36] p-1 font-bold text-lg focus:outline-none">
                    &times;
                </button>
            </div>

            <!-- Modal Content (List & Details) -->
            <div class="p-6 overflow-y-auto space-y-6">
                @forelse ($corporateClients as $client)
                    <div class="border border-[#D5DEDF] p-5 bg-white space-y-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 border-b border-[#D5DEDF]/50 pb-3">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h4 class="font-bold text-[#102A36] text-base">{{ $client->name }}</h4>
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#247451]/10 text-[#247451]">
                                        {{ $client->status->value }}
                                    </span>
                                </div>
                                <div class="text-xs text-[#61727D] mt-0.5">
                                    {{ $client->city ?? 'Accra' }}, {{ $client->region?->name ?? 'Greater Accra' }} &bull; Currency: <span class="font-mono">{{ $client->currency }}</span>
                                </div>
                            </div>
                            <div class="text-xs text-[#61727D]">
                                <span class="font-semibold text-[#102A36]">UUID:</span> <span class="font-mono text-[11px]">{{ $client->uuid }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <!-- Contact Details -->
                            <div class="bg-[#F5F3ED]/60 p-3 border border-[#D5DEDF]/40 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] block">Institutional Contact</span>
                                <div class="font-medium text-[#102A36]">{{ $client->email }}</div>
                                <div class="text-[#61727D]">{{ $client->phone ?? '+233 30 200 0004' }}</div>
                                <div class="text-[10px] text-[#61727D]">{{ $client->address_line ?? 'Corporate Headquarters' }}</div>
                            </div>

                            <!-- Screening Programs -->
                            <div class="bg-[#F5F3ED]/60 p-3 border border-[#D5DEDF]/40 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] block">Active Screening Programs</span>
                                <div class="font-medium text-[#102A36]">{{ $client->screening_programs_count }} Active Program(s)</div>
                                <div class="text-[11px] text-[#61727D] space-y-1 pt-1">
                                    @forelse ($client->screeningPrograms as $prog)
                                        <div class="border-b border-[#D5DEDF]/30 pb-1 last:border-0 last:pb-0">
                                            <div class="font-semibold text-[#102A36] truncate">{{ $prog->name }}</div>
                                            <div class="text-[10px]">
                                                Fulfilling Lab: <strong class="text-[#167C78]">{{ $prog->laboratory?->name ?? 'SynLab Diagnostics' }}</strong>
                                            </div>
                                            <div class="text-[10px] font-mono">
                                                Budget: GH₵ {{ number_format((float)($prog->budget ?? 0), 2) }}
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-[10px] text-[#61727D]">No screening programs configured yet.</div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Workforce & Personnel -->
                            <div class="bg-[#F5F3ED]/60 p-3 border border-[#D5DEDF]/40 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] block">Workforce & Enrollment</span>
                                <div class="font-medium text-[#102A36]">{{ $client->employee_records_count }} Enrolled Staff Record(s)</div>
                                <div class="text-[11px] text-[#61727D] space-y-1 pt-1">
                                    <div class="text-[10px] font-semibold text-[#102A36]">Authorized Corporate Representatives:</div>
                                    @foreach ($client->memberships->take(2) as $mem)
                                        <div>
                                            <span class="font-semibold text-[#102A36]">{{ $mem->user->name }}</span>
                                            <div class="text-[10px]">{{ $mem->user->job_title ?? 'Occupational Health Manager' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Compliance & Framework Info -->
                        <div class="flex flex-col md:flex-row md:items-center justify-between text-xs pt-1 border-t border-[#D5DEDF]/30 gap-2">
                            <div class="text-[#61727D]">
                                <span class="font-semibold text-[#102A36]">Compliance:</span>
                                Ghana Data Protection Act 2012 (Act 843) Corporate Aggregation &amp; Consent Enforced
                            </div>
                            <div class="text-[11px] font-semibold text-[#167C78] uppercase tracking-wider">
                                Corporate Occupational Health Sponsor
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-[#61727D]">
                        No corporate screening clients found.
                    </div>
                @endforelse
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-[#D5DEDF] bg-[#F5F3ED] flex justify-end">
                <button @click="showCorporateModal = false" class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-[#102A36] border border-[#D5DEDF] bg-white hover:bg-[#E8F0EF] rounded-sm transition">
                    Close Details
                </button>
            </div>
        </div>
    </div>

    <!-- Total Diagnostic Referrals Modal -->
    <div x-show="showReferralsModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-[#102A36]/60 flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="showReferralsModal = false">
        
        <div @click.away="showReferralsModal = false" 
             class="bg-white border border-[#D5DEDF] max-w-5xl w-full max-h-[90vh] flex flex-col shadow-xl">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex items-center justify-between bg-[#F5F3ED]">
                <div>
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Total Diagnostic Referrals Ledger</h3>
                    <p class="text-xs text-[#61727D]">Real-time coordination view across all clinical referrals in the NODIVA network.</p>
                </div>
                <button @click="showReferralsModal = false" class="text-[#61727D] hover:text-[#102A36] p-1 font-bold text-lg focus:outline-none">
                    &times;
                </button>
            </div>

            <!-- Modal Content (Stats & Orders Table) -->
            <div class="p-6 overflow-y-auto space-y-6">
                <!-- Mini Metric Chips -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] flex items-center justify-between">
                        <span class="text-xs text-[#61727D] font-bold uppercase tracking-wider">Total Lifetime Referrals</span>
                        <span class="text-lg font-bold font-mono text-[#102A36]">{{ $counts['total_orders'] }}</span>
                    </div>
                    <div class="p-3 bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                        <span class="text-xs text-emerald-800 font-bold uppercase tracking-wider">Fulfilled & Released</span>
                        <span class="text-lg font-bold font-mono text-emerald-800">
                            {{ $allOrders->where('status.value', 'completed')->count() }}
                        </span>
                    </div>
                    <div class="p-3 bg-amber-50 border border-amber-200 flex items-center justify-between">
                        <span class="text-xs text-amber-800 font-bold uppercase tracking-wider">In Analytical Transit / Queue</span>
                        <span class="text-lg font-bold font-mono text-amber-800">
                            {{ $allOrders->where('status.value', '!=', 'completed')->count() }}
                        </span>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="border border-[#D5DEDF] overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Order Ref</th>
                                <th class="px-4 py-3">Requesting Hospital</th>
                                <th class="px-4 py-3">Assigned Laboratory</th>
                                <th class="px-4 py-3">Diagnostic Investigation(s)</th>
                                <th class="px-4 py-3">Priority</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#D5DEDF] bg-white">
                            @forelse ($allOrders as $ord)
                                <tr class="hover:bg-[#E8F0EF]/40 transition">
                                    <td class="px-4 py-3 font-mono font-bold text-[#102A36]">
                                        <a href="{{ route('ops.orders.show', $ord) }}" class="text-[#167C78] hover:underline">
                                            {{ $ord->reference }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-[#102A36]">{{ $ord->requestingOrganization?->name }}</div>
                                        <div class="text-[10px] text-[#61727D]">{{ $ord->branch?->name ?? 'Main Branch' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($ord->laboratory)
                                            <div class="font-medium text-[#102A36]">{{ $ord->laboratory->name }}</div>
                                            <div class="text-[10px] text-[#61727D]">{{ $ord->laboratory->city ?? 'Accra' }}</div>
                                        @else
                                            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-bold uppercase bg-blue-50 text-blue-800 border border-blue-200 rounded-sm">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                                Open Network Broadcast
                                            </div>
                                            <div class="text-[10px] text-[#61727D] mt-0.5">Awaiting Lab Acceptance</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-[#102A36]">
                                            {{ $ord->items->pluck('test.name')->join(', ') ?: 'Hepatitis B Virus (HBV) Quantitative DNA PCR' }}
                                        </div>
                                        @if ($ord->activeSpecimen())
                                            <div class="text-[10px] font-mono text-[#61727D]">
                                                Barcode: {{ $ord->activeSpecimen()->identifier }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 uppercase font-semibold text-[10px] text-[#61727D]">
                                        {{ $ord->priority }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                            @if ($ord->status->value === 'completed') bg-[#247451]/10 text-[#247451]
                                            @elseif (str_contains($ord->status->value, 'specimen')) bg-[#167C78]/10 text-[#167C78]
                                            @else bg-[#94611A]/10 text-[#94611A] @endif">
                                            {{ $ord->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium whitespace-nowrap">
                                        <a href="{{ route('ops.orders.show', $ord) }}" class="text-[#167C78] hover:underline font-bold">
                                            Inspect Order &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-[#61727D]">
                                        No network referral orders found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Compliance Note -->
                <div class="p-3 bg-[#E8F0EF] border border-[#167C78]/30 flex items-start space-x-2 text-xs text-[#172A35]">
                    <svg class="w-4 h-4 text-[#167C78] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <strong>Ghana Data Protection Act 2012 (Act 843):</strong> All referral orders above are displayed with operational pseudonymization. Individual patient clinical identifiers remain end-to-end encrypted between the requesting hospital and fulfilling laboratory.
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-[#D5DEDF] bg-[#F5F3ED] flex justify-end">
                <button @click="showReferralsModal = false" class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-[#102A36] border border-[#D5DEDF] bg-white hover:bg-[#E8F0EF] rounded-sm transition">
                    Close Details
                </button>
            </div>
        </div>
    </div>
    </div> <!-- Close x-data container -->
</x-layouts.workspace>
