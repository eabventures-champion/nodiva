<x-layouts.workspace title="Invoices & Statements — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-hospital-sidebar active="invoices" />
    </x-slot:sidebar>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#102A36]">Invoices & Statements</h1>
            <p class="text-xs text-[#61727D]">Consolidated diagnostic billing, platform fee transparency, and payment status.</p>
        </div>
        <div class="text-xs text-[#61727D] font-mono">
            Currency Baseline: <strong>GHS (Ghanaian Cedi)</strong>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Total Billed GMV</div>
            <div class="text-2xl font-bold text-[#102A36] mt-1">GH₵ {{ number_format((float)$metrics['total_invoiced'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Total Settled</div>
            <div class="text-2xl font-bold text-[#247451] mt-1">GH₵ {{ number_format((float)$metrics['total_paid'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Outstanding Balance Due</div>
            <div class="text-2xl font-bold text-[#94611A] mt-1">GH₵ {{ number_format((float)$metrics['balance_due'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Unpaid Invoices</div>
            <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $metrics['count_outstanding'] }}</div>
        </div>
    </div>

    <!-- Status Filter -->
    <div class="flex gap-2 text-xs font-semibold overflow-x-auto pb-1">
        <a href="{{ route('hospital.invoices.index') }}" 
           class="px-3 py-1.5 border {{ empty($currentStatus) ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            All Statements
        </a>
        <a href="{{ route('hospital.invoices.index', ['status' => 'issued']) }}" 
           class="px-3 py-1.5 border {{ $currentStatus === 'issued' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            Issued / Pending Payment
        </a>
        <a href="{{ route('hospital.invoices.index', ['status' => 'paid']) }}" 
           class="px-3 py-1.5 border {{ $currentStatus === 'paid' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            Fully Settled
        </a>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white border border-[#D5DEDF]" x-data="{ selectedInvoice: null }">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Invoice Number</th>
                        <th class="px-6 py-3">Issue Date</th>
                        <th class="px-6 py-3">Associated Referral</th>
                        <th class="px-6 py-3">Fulfilling Lab</th>
                        <th class="px-6 py-3">Total Amount</th>
                        <th class="px-6 py-3">Balance Due</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($invoices as $inv)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">
                                <a href="{{ route('hospital.invoices.show', $inv) }}" class="text-[#167C78] hover:underline">
                                    {{ $inv->number }}
                                </a>
                            </td>
                            <td class="px-6 py-3.5 text-[#61727D]">{{ $inv->issued_on ? $inv->issued_on->format('M d, Y') : $inv->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-3.5">
                                @if ($inv->order)
                                    <a href="{{ route('hospital.orders.show', $inv->order) }}" class="text-[#167C78] hover:underline font-mono">
                                        {{ $inv->order->reference }}
                                    </a>
                                @else
                                    <span class="text-[#61727D]">Consolidated</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">{{ $inv->order?->laboratory?->name ?? 'NODIVA Network' }}</td>
                            <td class="px-6 py-3.5 font-bold text-[#102A36]">GH₵ {{ number_format((float)$inv->total, 2) }}</td>
                            <td class="px-6 py-3.5 font-bold {{ (float)$inv->balance_due > 0 ? 'text-[#94611A]' : 'text-[#247451]' }}">
                                GH₵ {{ number_format((float)$inv->balance_due, 2) }}
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                    @if ($inv->status === 'paid') bg-[#247451]/10 text-[#247451]
                                    @elseif ($inv->status === 'partially_paid') bg-[#167C78]/10 text-[#167C78]
                                    @else bg-[#94611A]/10 text-[#94611A] @endif">
                                    {{ str_replace('_', ' ', $inv->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right font-medium">
                                <a href="{{ route('hospital.invoices.show', $inv) }}" class="text-[#167C78] hover:underline font-bold">
                                    View Statement &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-[#61727D]">
                                No invoices or billing statements recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="p-4 border-t border-[#D5DEDF]">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</x-layouts.workspace>
