<x-layouts.workspace title="Hospital Referral Portal — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-hospital-sidebar active="dashboard" />
    </x-slot:sidebar>

    <!-- Header Stats -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-[#102A36]">Hospital Referral Operations</h1>
            <p class="text-xs text-[#61727D]">Active diagnostic requests sent to verified laboratory partners.</p>
        </div>
        <a href="{{ route('hospital.orders.create') }}" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition">
            + Create New Referral
        </a>
    </div>

    <!-- Stat Metric Boxes -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Total Referrals</div>
            <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $counts['total'] }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Specimens In Transit</div>
            <div class="text-2xl font-bold text-[#94611A] mt-1">{{ $counts['in_transit'] }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Laboratory Processing</div>
            <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $counts['processing'] }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Results Completed</div>
            <div class="text-2xl font-bold text-[#247451] mt-1">{{ $counts['completed'] }}</div>
        </div>
    </div>

    <!-- Orders Dense Operational Table -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-[#D5DEDF] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-4">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Recent Referral Orders</h2>
            <span class="text-[11px] sm:text-xs text-[#61727D]">Filtered by {{ $organization->name }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Order Ref</th>
                        <th class="px-6 py-3">Requested Tests</th>
                        <th class="px-6 py-3">Assigned Laboratory</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Priority</th>
                        <th class="px-6 py-3">Due Target</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($activeOrders as $order)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">{{ $order->reference }}</td>
                            <td class="px-6 py-3.5">{{ $order->items->pluck('test.name')->join(', ') }}</td>
                            <td class="px-6 py-3.5">{{ $order->laboratory?->name ?? 'Matching in progress...' }}</td>
                            <td class="px-6 py-3.5">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                    @if ($order->status->value === 'completed') bg-[#247451]/10 text-[#247451]
                                    @elseif (str_contains($order->status->value, 'specimen')) bg-[#167C78]/10 text-[#167C78]
                                    @else bg-[#94611A]/10 text-[#94611A] @endif">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 uppercase font-semibold text-[10px] text-[#61727D]">{{ $order->priority }}</td>
                            <td class="px-6 py-3.5 text-[#61727D]">{{ $order->due_at?->diffForHumans() ?? 'Pending accept' }}</td>
                            <td class="px-6 py-3.5 text-right font-medium">
                                <a href="{{ route('hospital.orders.show', $order) }}" class="text-[#167C78] hover:underline font-bold">View File &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-[#61727D]">
                                No external referral orders recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.workspace>
