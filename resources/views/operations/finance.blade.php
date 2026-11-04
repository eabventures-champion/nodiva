<x-layouts.workspace title="Financial Reconciliation — NODIVA Operations" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="finance" />
    </x-slot:sidebar>

    <div x-data="{ tariffModalOpen: false }" class="space-y-6">

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-1">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-[#102A36] tracking-tight">Financial Settlement & Reconciliation</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-xs bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/30 whitespace-nowrap">
                        Zero-Loss Parity Engine
                    </span>
                </div>
                <p class="text-xs text-[#61727D]">
                    Multi-party clearing: Diagnostic GMV, platform fees ({{ $feeSettings['platform_fee_percent'] }}% baseline), hospital invoices, and laboratory payables.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <button type="button" @click="tariffModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-[#F5F3ED] border border-[#167C78] text-[#167C78] text-xs font-bold uppercase tracking-wider rounded-xs transition shadow-2xs whitespace-nowrap">
                    <svg class="w-4 h-4 text-[#167C78] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Adjust Platform Tariff</span>
                </button>

                <!-- Trigger reconciliation button -->
                <form method="POST" action="{{ route('ops.finance.reconcile') }}" class="inline-flex items-center">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ now()->subDays(30)->toDateString() }}">
                    <input type="hidden" name="end_date" value="{{ now()->toDateString() }}">
                    <input type="hidden" name="currency" value="GHS">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition shadow-2xs whitespace-nowrap">
                        <svg class="w-4 h-4 text-white/90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Execute Reconciliation Run</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Platform Fee & Tariff Governance Card -->
        <div class="bg-white border border-[#D5DEDF] shadow-2xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between divide-y lg:divide-y-0 lg:divide-x divide-[#D5DEDF]">
                <!-- Left: Tariff Policy Overview -->
                <div class="p-4 lg:p-5 flex-1 bg-[#F5F3ED]/30 space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-[#102A36] text-white text-[9px] font-bold uppercase tracking-wider rounded-xs">
                            Tariff Policy
                        </span>
                        <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">
                            Active Platform Revenue & Clearing Model
                        </h2>
                    </div>
                    <p class="text-[11px] text-[#61727D] leading-relaxed">
                        Platform fees are calculated automatically during order quoting and reconciled against gross diagnostic turnover.
                    </p>
                </div>

                <!-- Right: Tariff Policy Metrics -->
                <div class="p-4 lg:p-5 grid grid-cols-1 sm:grid-cols-3 gap-6 bg-white shrink-0 sm:divide-x sm:divide-[#D5DEDF]">
                    <div class="space-y-1 sm:pr-4">
                        <span class="text-[10px] text-[#61727D] uppercase font-bold tracking-wider block">Baseline Take Rate</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xl font-bold font-mono text-[#167C78]">{{ $feeSettings['platform_fee_percent'] }}%</span>
                            <span class="text-[10px] text-[#61727D] font-medium uppercase">GMV share</span>
                        </div>
                    </div>

                    <div class="space-y-1 sm:px-6">
                        <span class="text-[10px] text-[#61727D] uppercase font-bold tracking-wider block">Settlement Model</span>
                        <span class="text-xs font-bold text-[#102A36] block leading-snug">
                            @if ($feeSettings['fee_bearer'] === 'laboratory')
                                Deducted from Lab Net Payable
                            @else
                                Billed to Referring Institution
                            @endif
                        </span>
                    </div>

                    <div class="space-y-1 sm:pl-6">
                        <span class="text-[10px] text-[#61727D] uppercase font-bold tracking-wider block">Logistics Dispatch</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xs font-bold text-[#102A36]">Hospital Determined</span>
                        </div>
                        <span class="text-[10px] text-[#61727D] block leading-tight">Set by referring hospital on dispatch</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#102A36] uppercase tracking-wider">Total Invoiced Volume</div>
            <div class="text-2xl font-bold text-[#102A36] mt-1 font-mono">GHS {{ number_format($totals['total_invoiced'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Settled & Paid</div>
            <div class="text-2xl font-bold text-[#247451] mt-1 font-mono">GHS {{ number_format($totals['total_paid'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Outstanding Receivables</div>
            <div class="text-2xl font-bold text-[#94611A] mt-1 font-mono">GHS {{ number_format($totals['outstanding'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#B42332] uppercase tracking-wider">Discrepancy Flags</div>
            <div class="text-2xl font-bold text-[#B42332] mt-1">{{ $totals['reconciliation_discrepancies'] }}</div>
        </div>
    </div>

    <!-- Reconciliation Runs Table -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Automated Clearing & Reconciliation History</h2>
            <span class="text-xs text-[#247451] font-semibold">Zero-Loss Parity Engine</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Audit UUID</th>
                        <th class="px-6 py-3">Audit Period</th>
                        <th class="px-6 py-3">Orders Audited</th>
                        <th class="px-6 py-3">Diagnostic GMV</th>
                        <th class="px-6 py-3">Platform Revenue</th>
                        <th class="px-6 py-3">Lab Net Payables</th>
                        <th class="px-6 py-3">Discrepancies</th>
                        <th class="px-6 py-3 text-right">Executed At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($runs as $run)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-mono text-[#61727D]">{{ substr($run->uuid, 0, 13) }}...</td>
                            <td class="px-6 py-3.5 font-semibold">{{ $run->period_start->format('M d') }} - {{ $run->period_end->format('M d, Y') }}</td>
                            <td class="px-6 py-3.5 font-bold">{{ $run->orders_checked }}</td>
                            <td class="px-6 py-3.5 font-mono">{{ $run->currency }} {{ $run->totals['total_gmv'] ?? '0.00' }}</td>
                            <td class="px-6 py-3.5 font-mono text-[#167C78] font-semibold">{{ $run->currency }} {{ $run->totals['total_platform_fees'] ?? '0.00' }}</td>
                            <td class="px-6 py-3.5 font-mono">{{ $run->currency }} {{ $run->totals['total_lab_payables'] ?? '0.00' }}</td>
                            <td class="px-6 py-3.5">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-sm {{ $run->discrepancy_count == 0 ? 'bg-[#247451]/10 text-[#247451]' : 'bg-[#B42332]/10 text-[#B42332]' }}">
                                    {{ $run->discrepancy_count }} Discrepancy
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right text-[#61727D]">
                                {{ $run->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-[#61727D]">
                                No reconciliation runs executed yet. Click "Execute Reconciliation Run" above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Platform Fee / Tariff Policy Modal -->
    <div x-show="tariffModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="tariffModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-lg w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-[#167C78]/10 text-[#167C78] rounded-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Dynamic Platform Tariff Configuration</h3>
                    </div>
                    <button @click="tariffModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form method="POST" action="{{ route('ops.finance.settings') }}" class="space-y-4 text-xs">
                    @csrf

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">
                            Default Baseline Platform Fee Rate (%) <span class="text-rose-600">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" max="100" name="platform_fee_percent" 
                                   value="{{ $feeSettings['platform_fee_percent'] }}" required
                                   class="w-full border border-[#D5DEDF] p-2 pr-8 focus:border-[#167C78] focus:outline-none font-mono font-bold text-[#102A36]">
                            <span class="absolute right-3 top-2 text-[#61727D] font-bold">%</span>
                        </div>
                        <p class="text-[10px] text-[#61727D] mt-1">
                            Applied dynamically to all diagnostic referral orders unless overridden by a custom institutional contract rule.
                        </p>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">
                            Platform Fee Bearer Model <span class="text-rose-600">*</span>
                        </label>
                        <select name="fee_bearer" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none font-bold text-[#102A36]">
                            <option value="laboratory" {{ $feeSettings['fee_bearer'] === 'laboratory' ? 'selected' : '' }}>
                                Deducted from Laboratory Settlement (Standard Revenue Share)
                            </option>
                            <option value="institution" {{ $feeSettings['fee_bearer'] === 'institution' ? 'selected' : '' }}>
                                Invoiced Directly to Referring Hospital (Surcharge Model)
                            </option>
                        </select>
                        <p class="text-[10px] text-[#61727D] mt-1">
                            Defines which party absorbs the platform clearing charge during automatic invoice generation.
                        </p>
                    </div>

                    <!-- Logistics Dispatch Fee Policy Notice -->
                    <div class="p-3 bg-white border border-[#D5DEDF] rounded-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-[#102A36] uppercase tracking-wider">Logistics Dispatch Fee Policy</span>
                            <span class="text-[9px] font-bold uppercase px-2 py-0.5 bg-[#167C78]/10 text-[#167C78] rounded-xs">Hospital-Determined</span>
                        </div>
                        <p class="text-[11px] text-[#61727D] leading-relaxed">
                            Logistics dispatch fees are determined directly by referring hospitals during referral order creation, as referring institutions initiate courier specimen transport from their phlebotomy facilities.
                        </p>
                    </div>

                    <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] text-[#61727D] space-y-1 text-[11px]">
                        <div class="font-bold text-[#102A36]">Audit & Compliance Notice</div>
                        <p class="leading-relaxed">
                            Updates take effect immediately on all subsequent diagnostic referral calculations. Modifications are recorded in the SHA-256 cryptographic audit trail.
                        </p>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-2">
                        <button type="button" @click="tariffModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] text-[#61727D] font-bold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider transition shadow-sm">
                            Save Tariff Policy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.workspace>
