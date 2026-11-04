<x-layouts.workspace title="Logistics & Chain of Custody — NODIVA Operations" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="logistics" />
    </x-slot:sidebar>

    <div class="space-y-2">
        <h1 class="text-2xl font-bold text-[#102A36]">Specimen Logistics & Custody Monitoring</h1>
        <p class="text-xs text-[#61727D]">Track physical diagnostic sample transit, temperature compliance, and handover integrity.</p>
    </div>

    <!-- Logistics Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Dispatches In Transit</div>
            <div class="text-2xl font-bold text-[#94611A] mt-1">{{ $counts['in_transit'] }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Scheduled Couriers</div>
            <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $counts['scheduled'] }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Delivered to Labs</div>
            <div class="text-2xl font-bold text-[#247451] mt-1">{{ $counts['delivered'] }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#102A36] uppercase tracking-wider">Total Tracked Containers</div>
            <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $counts['total_specimens'] }}</div>
        </div>
    </div>

    <!-- Active Dispatches Table -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Courier Dispatches & Transport Runs</h2>
            <span class="text-xs text-[#61727D]">Real-time carrier custody</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Carrier / Tracking</th>
                        <th class="px-6 py-3">Referral Order</th>
                        <th class="px-6 py-3">Origin (Hospital)</th>
                        <th class="px-6 py-3">Destination (Lab)</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Dispatched</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($dispatches as $dispatch)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5">
                                <div class="font-bold text-[#102A36]">{{ $dispatch->carrier_name }}</div>
                                <div class="font-mono text-[10px] text-[#167C78]">{{ $dispatch->tracking_reference ?? 'Pending reference' }}</div>
                            </td>
                            <td class="px-6 py-3.5 font-mono font-bold">{{ $dispatch->order?->reference }}</td>
                            <td class="px-6 py-3.5">{{ $dispatch->order?->requestingOrganization?->name }}</td>
                            <td class="px-6 py-3.5">{{ $dispatch->order?->laboratory?->name }}</td>
                            <td class="px-6 py-3.5">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#94611A]/10 text-[#94611A]">
                                    {{ $dispatch->status }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right text-[#61727D]">
                                {{ $dispatch->dispatched_at?->diffForHumans() ?? 'Scheduled' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-[#61727D]">
                                No active courier dispatches currently in transit.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.workspace>
