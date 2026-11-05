<x-layouts.workspace title="Specimen Reception & Accessioning — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-lab-sidebar active="reception" />
    </x-slot:sidebar>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#102A36]">Specimen Reception & Accessioning</h1>
            <p class="text-xs text-[#61727D]">Incoming order acceptance, courier delivery handover, and pre-analytical specimen verification.</p>
        </div>
        <div class="text-xs text-[#61727D] font-mono">
            Accredited Laboratory Intake Bay • <strong>{{ $organization->name }}</strong>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 text-xs">
            {{ session('error') }}
        </div>
    @endif

    <!-- Metric Stat Boxes -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Awaiting Acceptance</div>
            <div class="text-2xl font-bold text-[#94611A] mt-1">{{ $metrics['awaiting_acceptance'] }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Courier In Transit</div>
            <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $metrics['in_transit'] }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Accessioned & Received</div>
            <div class="text-2xl font-bold text-[#247451] mt-1">{{ $metrics['received'] }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#B42332] uppercase tracking-wider">Specimens Rejected</div>
            <div class="text-2xl font-bold text-[#B42332] mt-1">{{ $metrics['rejected'] }}</div>
        </div>
    </div>

    <!-- Section 1: Referral Orders Awaiting Acceptance -->
    @if ($pendingAcceptanceOrders->isNotEmpty())
        <div class="bg-white border-2 border-[#94611A]/40">
            <div class="px-6 py-4 bg-[#94611A]/5 border-b border-[#94611A]/20 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#94611A] animate-pulse"></span>
                    <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">New Referral Orders Offered to Your Laboratory</h2>
                </div>
                <span class="text-xs font-semibold text-[#94611A]">{{ $pendingAcceptanceOrders->count() }} action required</span>
            </div>

            <div class="divide-y divide-[#D5DEDF]">
                @foreach ($pendingAcceptanceOrders as $order)
                    @php
                        $isBroadcast = is_null($order->laboratory_id);
                    @endphp
                    <div class="p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white hover:bg-[#F5F3ED]/40 transition">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <span class="font-mono font-bold text-sm text-[#102A36]">{{ $order->reference }}</span>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase bg-amber-100 text-amber-800 rounded-sm">
                                    {{ $order->priority }} Priority
                                </span>
                                @if ($isBroadcast)
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase bg-blue-100 text-blue-800 rounded-sm flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                        Open Network Broadcast (First to Claim)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 rounded-sm">
                                        Directly Assigned
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-[#102A36] flex items-center gap-2">
                                <span>Referring Institution: <strong>{{ $order->requestingOrganization?->name }}</strong></span>
                                @if ($order->requestingOrganization)
                                    <a href="{{ route('lab.communications.create', ['partner_id' => $order->requestingOrganization->id]) }}" 
                                       class="inline-flex items-center gap-1 text-[11px] text-[#167C78] hover:underline font-semibold">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        <span>Message Hospital</span>
                                    </a>
                                @endif
                            </div>
                            <div class="text-xs text-[#61727D]">
                                Tests: <strong>{{ $order->items->pluck('test.name')->join(', ') }}</strong> • Indication: <em>{{ $order->clinical_indication }}</em>
                            </div>

                            @if ($order->collection_method === 'courier')
                                <div class="mt-2 text-[11px] text-[#61727D] flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                    <span>Transport: <strong>Hospital Cold-Chain Courier Handover</strong> (Specimens collected at referring facility)</span>
                                </div>
                            @else
                                <div class="mt-2 text-[11px] text-[#167C78] flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>Transport: <strong>Patient Direct Walk-In</strong></span>
                                </div>
                            @endif
                        </div>

                        <form id="accept-order-{{ $order->id }}" action="{{ route('lab.orders.accept', $order) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2.5 {{ $isBroadcast ? 'bg-[#102A36] hover:bg-[#193B49]' : 'bg-[#167C78] hover:bg-[#136663]' }} text-white text-xs font-bold uppercase tracking-wider transition shadow-sm flex items-center gap-1.5 shrink-0 cursor-pointer">
                                @if ($isBroadcast)
                                    <span>Claim Referral & Confirm &rarr;</span>
                                @else
                                    <span>Accept Referral Order &rarr;</span>
                                @endif
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Section 2: Barcoded Specimens Log & Intake -->
    <div class="bg-white border border-[#D5DEDF]" x-data="{ rejectModalOpen: false, rejectSpecimenId: '', rejectIdentifier: '' }">
        <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Specimen Container Log</h2>
            <div class="flex gap-2 text-xs font-semibold">
                <a href="{{ route('lab.reception.index') }}" 
                   class="px-2.5 py-1 border {{ empty($currentStatus) ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF]' }}">
                    All
                </a>
                <a href="{{ route('lab.reception.index', ['status' => 'in_transit']) }}" 
                   class="px-2.5 py-1 border {{ $currentStatus === 'in_transit' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF]' }}">
                    In Transit
                </a>
                <a href="{{ route('lab.reception.index', ['status' => 'received']) }}" 
                   class="px-2.5 py-1 border {{ $currentStatus === 'received' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF]' }}">
                    Received
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Specimen Identifier</th>
                        <th class="px-6 py-3">Order Ref</th>
                        <th class="px-6 py-3">Referring Hospital</th>
                        <th class="px-6 py-3">Container Tube</th>
                        <th class="px-6 py-3">Handling Condition</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Accessioning Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($specimens as $sp)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">{{ $sp->identifier }}</td>
                            <td class="px-6 py-3.5 font-mono text-[#167C78] font-bold">{{ $sp->order->reference }}</td>
                            <td class="px-6 py-3.5">
                                <div class="font-medium text-[#102A36]">{{ $sp->order->requestingOrganization?->name ?? 'Hospital' }}</div>
                                @if ($sp->order->requestingOrganization)
                                    <a href="{{ route('lab.communications.create', ['partner_id' => $sp->order->requestingOrganization->id]) }}" 
                                       class="inline-flex items-center gap-1 text-[10px] text-[#167C78] hover:underline">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                        <span>Message</span>
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 font-bold text-[#102A36]">{{ $sp->type?->container }}</td>
                            <td class="px-6 py-3.5 text-[#61727D] capitalize">{{ $sp->type?->transport_requirements }}</td>
                            <td class="px-6 py-3.5">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                    @if ($sp->status->value === 'received' || $sp->status->value === 'accepted') bg-[#247451]/10 text-[#247451]
                                    @elseif ($sp->status->value === 'in_transit') bg-[#167C78]/10 text-[#167C78]
                                    @elseif ($sp->status->value === 'rejected') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-700 @endif">
                                    {{ $sp->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right font-medium space-x-2">
                                @if ($sp->status->value === 'in_transit' || $sp->status->value === 'collected')
                                    <form action="{{ route('lab.specimens.receive', $sp) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="process_immediately" value="1">
                                        <button type="submit" class="px-3 py-1 bg-[#167C78] hover:bg-[#136663] text-white text-[11px] font-bold uppercase transition">
                                            Verify & Accept
                                        </button>
                                    </form>
                                    <button type="button" @click="rejectSpecimenId = '{{ $sp->id }}'; rejectIdentifier = '{{ $sp->identifier }}'; rejectModalOpen = true"
                                            class="px-2.5 py-1 border border-red-300 text-red-700 hover:bg-red-50 text-[11px] font-bold uppercase transition">
                                        Reject
                                    </button>
                                @elseif ($sp->status->value === 'received' || $sp->status->value === 'accepted')
                                    <a href="{{ route('lab.results.create', $sp->order) }}" class="px-3 py-1 bg-[#102A36] hover:bg-[#193B49] text-white text-[11px] font-bold uppercase transition">
                                        Enter Results &rarr;
                                    </a>
                                @else
                                    <span class="text-[#61727D] text-[11px]">Logged</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-[#61727D]">
                                No specimens currently logged in the intake bay.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($specimens->hasPages())
            <div class="p-4 border-t border-[#D5DEDF]">
                {{ $specimens->links() }}
            </div>
        @endif

        <!-- Rejection Reason Modal -->
        <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="rejectModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-red-700 uppercase tracking-wider">Reject Specimen Container</h3>
                    <button @click="rejectModalOpen = false" class="text-gray-400 hover:text-black font-bold">&times;</button>
                </div>

                <form :action="'/lab/specimens/' + rejectSpecimenId + '/reject'" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <div class="text-[#61727D] mb-1">Specimen: <strong class="font-mono text-[#102A36]" x-text="rejectIdentifier"></strong></div>
                        <label class="block font-bold text-[#102A36] mb-1">Documented Pre-Analytical Rejection Reason <span class="text-red-500">*</span></label>
                        <select name="rejection_reason" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                            <option value="">-- Select Rejection Criteria --</option>
                            <option value="Severe hemolysis exceeding analytical limits">Severe hemolysis exceeding analytical limits</option>
                            <option value="Specimen clotted (EDTA / Anticoagulant tube failure)">Specimen clotted (EDTA / Anticoagulant tube failure)</option>
                            <option value="Incorrect specimen container / collection tube">Incorrect specimen container / collection tube</option>
                            <option value="Insufficient volume for requested diagnostic test (QNS)">Insufficient volume for requested diagnostic test (QNS)</option>
                            <option value="Cold-chain temperature excursion during courier transport">Cold-chain temperature excursion during courier transport</option>
                            <option value="Container damaged / leaked in transit">Container damaged / leaked in transit</option>
                            <option value="Specimen barcode unreadable / mismatched requisition">Specimen barcode unreadable / mismatched requisition</option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white font-bold uppercase tracking-wider">
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.workspace>
