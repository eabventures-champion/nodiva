<x-layouts.workspace title="Referral Orders — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-hospital-sidebar active="dashboard" />
    </x-slot:sidebar>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#102A36]">Hospital Diagnostic Referrals</h1>
            <p class="text-xs text-[#61727D]">Track external patient diagnostic requests, courier custody, and authorized results.</p>
        </div>
        <a href="{{ route('hospital.orders.create') }}" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition">
            + Create New Referral
        </a>
    </div>

    <!-- Status Filters Bar -->
    <div class="flex gap-2 text-xs font-semibold overflow-x-auto pb-1">
        <a href="{{ route('hospital.orders.index') }}" 
           class="px-3 py-1.5 border {{ empty($currentStatus) ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            All Referrals
        </a>
        <a href="{{ route('hospital.orders.index', ['status' => 'awaiting_lab_acceptance']) }}" 
           class="px-3 py-1.5 border {{ $currentStatus === 'awaiting_lab_acceptance' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            Awaiting Acceptance
        </a>
        <a href="{{ route('hospital.orders.index', ['status' => 'specimen_collected']) }}" 
           class="px-3 py-1.5 border {{ $currentStatus === 'specimen_collected' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            Collected & Ready
        </a>
        <a href="{{ route('hospital.orders.index', ['status' => 'in_transit']) }}" 
           class="px-3 py-1.5 border {{ $currentStatus === 'in_transit' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            In Transit (Courier)
        </a>
        <a href="{{ route('hospital.orders.index', ['status' => 'processing']) }}" 
           class="px-3 py-1.5 border {{ $currentStatus === 'processing' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            In Laboratory Analysis
        </a>
        <a href="{{ route('hospital.orders.index', ['status' => 'completed']) }}" 
           class="px-3 py-1.5 border {{ $currentStatus === 'completed' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            Completed & Released
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Order Ref</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Requested Tests</th>
                        <th class="px-6 py-3">Assigned Laboratory</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Priority</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">
                                <a href="{{ route('hospital.orders.show', $order) }}" class="text-[#167C78] hover:underline">
                                    {{ $order->reference }}
                                </a>
                            </td>
                            <td class="px-6 py-3.5 text-[#61727D]">{{ $order->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-3.5">{{ $order->items->pluck('test.name')->join(', ') }}</td>
                            <td class="px-6 py-3.5">
                                @if ($order->laboratory)
                                    {{ $order->laboratory->name }}
                                @else
                                    <span class="text-blue-700 font-semibold text-xs">Open Broadcast (Pending Claim)</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                    @if ($order->status->value === 'completed') bg-[#247451]/10 text-[#247451]
                                    @elseif (str_contains($order->status->value, 'specimen') || $order->status->value === 'in_transit') bg-[#167C78]/10 text-[#167C78]
                                    @else bg-[#94611A]/10 text-[#94611A] @endif">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 uppercase font-semibold text-[10px] text-[#61727D]">{{ $order->priority }}</td>
                            <td class="px-6 py-3.5 text-right font-medium">
                                <a href="{{ route('hospital.orders.show', $order) }}" class="text-[#167C78] hover:underline font-bold">
                                    View File &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-[#61727D]">
                                No diagnostic referral orders found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="p-4 border-t border-[#D5DEDF]">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-layouts.workspace>
