<x-layouts.workspace title="Specimen Custody & Courier Handover — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-hospital-sidebar active="custody" />
    </x-slot:sidebar>

    <div x-data="{
        dispatchModalOpen: false,
        selectedOrderId: '{{ $readyToDispatchOrders->first()?->id ?? '' }}',
        ordersData: @js($readyOrdersData ?? []),
        carrierName: 'Medical Express Courier (Ghana)',
        trackingRef: 'MX-GH-' + Math.floor(10000 + Math.random() * 90000),
        pickupLocation: '{{ $organization->name }} — Central Specimen Handover Bay',
        dispatchFee: '{{ $readyToDispatchOrders->first()?->logistics_charge ?? '35.00' }}',
        openDispatchFor(orderId) {
            this.selectedOrderId = String(orderId);
            this.syncSelectedOrder();
            this.dispatchModalOpen = true;
        },
        syncSelectedOrder() {
            if (this.selectedOrderId && this.ordersData[this.selectedOrderId]) {
                this.dispatchFee = this.ordersData[this.selectedOrderId].logistics_charge;
            }
        },
        get selectedOrderInfo() {
            return this.ordersData[this.selectedOrderId] || null;
        }
    }">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#102A36]">Specimen Custody & Courier Handover</h1>
                <p class="text-xs text-[#61727D]">End-to-end barcoded biological specimen tracking from hospital phlebotomy to laboratory reception.</p>
            </div>
            @if ($readyToDispatchOrders->isNotEmpty())
                <button @click="dispatchModalOpen = true" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition">
                    + Prepare Courier Dispatch
                </button>
            @endif
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                {{ session('success') }}
            </div>
        @endif

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Pending Phlebotomy</div>
                <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $metrics['pending'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Collected / Ready for Courier</div>
                <div class="text-2xl font-bold text-[#94611A] mt-1">{{ $metrics['collected'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">In Courier Transit</div>
                <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $metrics['in_transit'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Received & Verified by Lab</div>
                <div class="text-2xl font-bold text-[#247451] mt-1">{{ $metrics['received'] }}</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex gap-2 text-xs font-semibold overflow-x-auto pb-1">
            <a href="{{ route('hospital.specimens.index') }}" 
               class="px-3 py-1.5 border {{ empty($currentStatus) ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
                All Specimens
            </a>
            <a href="{{ route('hospital.specimens.index', ['status' => 'pending_collection']) }}" 
               class="px-3 py-1.5 border {{ $currentStatus === 'pending_collection' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
                Pending Phlebotomy ({{ $metrics['pending'] }})
            </a>
            <a href="{{ route('hospital.specimens.index', ['status' => 'collected']) }}" 
               class="px-3 py-1.5 border {{ $currentStatus === 'collected' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
                Ready for Dispatch ({{ $metrics['collected'] }})
            </a>
            <a href="{{ route('hospital.specimens.index', ['status' => 'in_transit']) }}" 
               class="px-3 py-1.5 border {{ $currentStatus === 'in_transit' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
                In Transit ({{ $metrics['in_transit'] }})
            </a>
            <a href="{{ route('hospital.specimens.index', ['status' => 'received']) }}" 
               class="px-3 py-1.5 border {{ $currentStatus === 'received' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
                Lab Received & Accepted ({{ $metrics['received'] }})
            </a>
        </div>

        <!-- Specimen Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Barcode Identifier</th>
                            <th class="px-6 py-3">Referral Order</th>
                            <th class="px-6 py-3">Container Tube</th>
                            <th class="px-6 py-3">Handling Requirements</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Assigned Lab</th>
                            <th class="px-6 py-3 text-right">Phlebotomy Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($specimens as $sp)
                            <tr class="hover:bg-[#E8F0EF]/40 transition">
                                <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">
                                    {{ $sp->identifier }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <a href="{{ route('hospital.orders.show', $sp->order) }}" class="text-[#167C78] hover:underline font-mono font-bold">
                                        {{ $sp->order->reference }}
                                    </a>
                                </td>
                                <td class="px-6 py-3.5 font-bold text-[#102A36]">
                                    {{ $sp->type?->container }}
                                </td>
                                <td class="px-6 py-3.5 text-[#61727D]">
                                    <span class="capitalize">{{ $sp->type?->transport_requirements }}</span> • Stability: {{ $sp->type?->stability_hours }}h
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                        @if ($sp->status->value === 'accepted') bg-[#247451]/10 text-[#247451] border border-[#247451]/20
                                        @elseif ($sp->status->value === 'received') bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/20
                                        @elseif ($sp->status->value === 'in_transit') bg-[#167C78]/10 text-[#167C78]
                                        @elseif ($sp->status->value === 'collected') bg-[#94611A]/10 text-[#94611A]
                                        @elseif ($sp->status->value === 'rejected') bg-[#B42332]/10 text-[#B42332]
                                        @else bg-gray-100 text-gray-700 @endif">
                                        {{ $sp->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-[#102A36] font-medium">
                                    @if ($sp->order->laboratory)
                                        {{ $sp->order->laboratory->name }}
                                    @else
                                        <span class="text-amber-700 text-xs font-semibold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Open Broadcast (Matching...)
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right font-medium">
                                    @if ($sp->status->value === 'pending_collection')
                                        <form action="{{ route('hospital.specimens.collect', $sp) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-[#167C78] hover:bg-[#136663] text-white text-[11px] font-bold uppercase transition cursor-pointer">
                                                Record Collection
                                            </button>
                                        </form>
                                    @elseif ($sp->status->value === 'collected')
                                        @if ($sp->order->laboratory_id && $sp->order->status !== \App\Domain\Referrals\Enums\OrderStatus::Matching)
                                            <button @click="openDispatchFor('{{ $sp->order_id }}')"
                                                    class="px-3 py-1 bg-[#94611A] hover:bg-[#7d5216] text-white text-[11px] font-bold uppercase transition cursor-pointer">
                                                Dispatch Courier
                                            </button>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-semibold rounded-xs" 
                                                  title="This open broadcast referral is awaiting acceptance from a partner laboratory before courier dispatch can occur.">
                                                <svg class="w-3 h-3 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>Awaiting Lab Claim</span>
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-[#61727D] text-[11px]">Tracked</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-[#61727D]">
                                    No specimens recorded yet.
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
        </div>

        <!-- Courier Dispatch Handover Modal -->
        <div x-show="dispatchModalOpen" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="dispatchModalOpen = false" 
                 class="bg-white border border-[#D5DEDF] max-w-lg w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Courier Dispatch Consignment</h3>
                    <button @click="dispatchModalOpen = false" class="text-gray-400 hover:text-black font-bold">&times;</button>
                </div>

                <form action="{{ route('hospital.specimens.dispatch') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Select Order for Specimen Handover</label>
                        @if ($readyToDispatchOrders->isNotEmpty())
                            <select name="order_id" x-model="selectedOrderId" @change="syncSelectedOrder()" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                                <option value="">-- Choose referral order with collected samples --</option>
                                @foreach ($readyToDispatchOrders as $rOrder)
                                    <option value="{{ $rOrder->id }}">
                                        {{ $rOrder->reference }} &rarr; {{ $rOrder->laboratory?->name }} ({{ $rOrder->specimens->count() }} tube(s))
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-xs">
                                <strong>No orders currently ready for courier handover.</strong>
                                <p class="text-[11px] text-amber-700 mt-1">Orders with collected specimens must have an assigned partner laboratory before courier dispatch can be booked. Open broadcast orders will appear here as soon as an accredited partner laboratory accepts them.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Fulfilling Destination Info Box -->
                    <div x-show="selectedOrderInfo" class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] rounded-xs space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-[#61727D]">Fulfilling Laboratory:</span>
                            <span class="font-bold text-[#102A36]" x-text="selectedOrderInfo ? selectedOrderInfo.lab_name : ''"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-[#61727D]">Destination Bay:</span>
                            <span class="text-[#102A36] font-medium" x-text="selectedOrderInfo ? selectedOrderInfo.lab_location : ''"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] pt-1 border-t border-[#D5DEDF]/60">
                            <span class="text-[#61727D]">Logistics Route Proximity:</span>
                            <span class="px-1.5 py-0.2 font-bold uppercase text-[9px] rounded-xs"
                                  :class="selectedOrderInfo?.is_same_region ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
                                  x-text="selectedOrderInfo?.is_same_region ? 'Intra-Regional Courier' : 'Inter-Regional Transit'"></span>
                        </div>
                    </div>

                    <!-- Logistics Dispatch Charge Determined by Handover Staff -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block font-bold text-[#102A36]">Logistics Dispatch Charge (GHS) *</label>
                            <span class="text-[10px] text-[#61727D]" x-show="selectedOrderInfo">
                                <span x-text="selectedOrderInfo?.is_same_region ? 'Local rate (standard GHS 35.00)' : 'Cross-region transit (standard GHS 75.00)'"></span>
                            </span>
                        </div>
                        <input type="number" step="0.01" min="0" name="logistics_charge" x-model="dispatchFee" required
                               class="w-full border border-[#D5DEDF] p-2 font-mono font-bold text-sm text-[#102A36] bg-white focus:border-[#167C78] focus:outline-none">
                        <p class="text-[10px] text-[#61727D] mt-1">
                            Enter the confirmed courier transportation charge for this consignment handover.
                        </p>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Carrier / Logistics Provider</label>
                        <input type="text" name="carrier_name" x-model="carrierName" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Courier Waybill / Tracking Reference</label>
                        <input type="text" name="tracking_reference" x-model="trackingRef" required class="w-full border border-[#D5DEDF] p-2 font-mono focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Hospital Handover Location</label>
                        <input type="text" name="pickup_location" x-model="pickupLocation" class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="dispatchModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D] cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" 
                                @if ($readyToDispatchOrders->isEmpty()) disabled @endif
                                class="px-4 py-2 @if ($readyToDispatchOrders->isEmpty()) bg-gray-400 cursor-not-allowed @else bg-[#167C78] hover:bg-[#136663] cursor-pointer @endif text-white font-bold uppercase tracking-wider transition">
                            Hand Over to Courier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.workspace>
