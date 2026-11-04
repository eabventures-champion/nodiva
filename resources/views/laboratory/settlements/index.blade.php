<x-layouts.workspace title="Settlement Statements & Receivables — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-lab-sidebar active="settlements" />
    </x-slot:sidebar>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#102A36]">Laboratory Settlements & Receivables</h1>
            <p class="text-xs text-[#61727D]">Net diagnostic payouts, transparent platform fee accounting, and settlement statements.</p>
        </div>
        <div class="text-xs text-[#61727D] font-mono">
            Platform Baseline Fee: <strong>12.00%</strong> • Currency: <strong>GHS (Ghanaian Cedi)</strong>
        </div>
    </div>

    <!-- Metric Stat Boxes -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Gross Fulfilled GMV</div>
            <div class="text-2xl font-bold text-[#102A36] mt-1">GH₵ {{ number_format((float)$metrics['gross_service_value'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Network Platform Fees</div>
            <div class="text-2xl font-bold text-[#94611A] mt-1">GH₵ {{ number_format((float)$metrics['platform_fees'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Net Laboratory Payout</div>
            <div class="text-2xl font-bold text-[#247451] mt-1">GH₵ {{ number_format((float)$metrics['laboratory_payable'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Completed Referral Orders</div>
            <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $metrics['completed_orders_count'] }}</div>
        </div>
    </div>

    <!-- Order Financials Table -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Diagnostic Order Payout Ledger</h2>
            <span class="text-xs text-[#61727D]">Reconciled per referral completion</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Order Ref</th>
                        <th class="px-6 py-3">Referring Hospital</th>
                        <th class="px-6 py-3">Requested Tests</th>
                        <th class="px-6 py-3 text-right">Service Value (GHS)</th>
                        <th class="px-6 py-3 text-right">Platform Fee (12%)</th>
                        <th class="px-6 py-3 text-right">Net Lab Payable</th>
                        <th class="px-6 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($orderFinancials as $f)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">
                                {{ $f->order?->reference }}
                            </td>
                            <td class="px-6 py-3.5">{{ $f->order?->requestingOrganization?->name }}</td>
                            <td class="px-6 py-3.5">{{ $f->order?->items->pluck('test.name')->join(', ') }}</td>
                            <td class="px-6 py-3.5 text-right font-mono font-bold text-[#102A36]">
                                GH₵ {{ number_format((float)$f->service_value, 2) }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-mono text-[#94611A]">
                                - GH₵ {{ number_format((float)$f->platform_fee, 2) }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-mono font-bold text-[#247451]">
                                GH₵ {{ number_format((float)$f->laboratory_payable, 2) }}
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#247451]/10 text-[#247451] border border-[#247451]/30">
                                    Reconciled
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-[#61727D]">
                                No fulfilled diagnostic order financials recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orderFinancials->hasPages())
            <div class="p-4 border-t border-[#D5DEDF]">
                {{ $orderFinancials->links() }}
            </div>
        @endif
    </div>

    <!-- Settlement Consignments History -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="px-6 py-4 border-b border-[#D5DEDF]">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Settlement Disbursals & Banking Remittance</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Settlement Batch</th>
                        <th class="px-6 py-3">Billing Period</th>
                        <th class="px-6 py-3 text-right">Gross GMV</th>
                        <th class="px-6 py-3 text-right">Fee Retained</th>
                        <th class="px-6 py-3 text-right">Net Transferred</th>
                        <th class="px-6 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($settlements as $s)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">{{ $s->settlement_reference }}</td>
                            <td class="px-6 py-3.5 text-[#61727D]">{{ $s->period_start?->format('M d') }} - {{ $s->period_end?->format('M d, Y') }}</td>
                            <td class="px-6 py-3.5 text-right font-mono">GH₵ {{ number_format((float)$s->gross_service_value, 2) }}</td>
                            <td class="px-6 py-3.5 text-right font-mono text-[#94611A]">- GH₵ {{ number_format((float)$s->platform_fees, 2) }}</td>
                            <td class="px-6 py-3.5 text-right font-mono font-bold text-[#247451]">GH₵ {{ number_format((float)$s->net_payable, 2) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-emerald-100 text-emerald-800">
                                    Disbursed
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-6 text-center text-[#61727D]">
                                Automatic settlement batch scheduled for the current bi-weekly cycle.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.workspace>
