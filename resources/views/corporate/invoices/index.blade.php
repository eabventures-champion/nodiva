<x-layouts.workspace title="Program Invoices — Corporate Health Screening — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-corporate-sidebar active="billing" />
    </x-slot:sidebar>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#102A36]">Corporate Program Invoices</h1>
            <p class="text-xs text-[#61727D]">Consolidated billing statements for corporate wellness and occupational health campaigns.</p>
        </div>
        <div class="text-xs text-[#61727D] font-mono">
            Billing Unit: <strong>Corporate Sponsor ({{ $organization->name }})</strong>
        </div>
    </div>

    <!-- Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Total Invoiced</div>
            <div class="text-2xl font-bold text-[#102A36] mt-1">GH₵ {{ number_format((float)$metrics['total_invoiced'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Settled to Date</div>
            <div class="text-2xl font-bold text-[#247451] mt-1">GH₵ {{ number_format((float)$metrics['total_paid'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Outstanding Balance Due</div>
            <div class="text-2xl font-bold text-[#94611A] mt-1">GH₵ {{ number_format((float)$metrics['balance_due'], 2) }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Unpaid Statements</div>
            <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $metrics['count_outstanding'] }}</div>
        </div>
    </div>

    <!-- Status Filters -->
    <div class="flex gap-2 text-xs font-semibold overflow-x-auto pb-1">
        <a href="{{ route('corporate.invoices.index') }}" 
           class="px-3 py-1.5 border {{ empty($currentStatus) ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            All Invoices
        </a>
        <a href="{{ route('corporate.invoices.index', ['status' => 'issued']) }}" 
           class="px-3 py-1.5 border {{ $currentStatus === 'issued' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            Pending Payment
        </a>
        <a href="{{ route('corporate.invoices.index', ['status' => 'paid']) }}" 
           class="px-3 py-1.5 border {{ $currentStatus === 'paid' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            Fully Settled
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Invoice Number</th>
                        <th class="px-6 py-3">Screening Program</th>
                        <th class="px-6 py-3">Partner Laboratory</th>
                        <th class="px-6 py-3">Issued On</th>
                        <th class="px-6 py-3">Due Date</th>
                        <th class="px-6 py-3">Total Amount</th>
                        <th class="px-6 py-3">Balance Due</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($invoices as $inv)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">
                                <a href="{{ route('corporate.invoices.show', $inv) }}" class="text-[#167C78] hover:underline">
                                    {{ $inv->number }}
                                </a>
                            </td>
                            <td class="px-6 py-3.5 font-semibold text-[#102A36]">
                                {{ $inv->program?->name ?? 'Corporate Health Consignment' }}
                            </td>
                            <td class="px-6 py-3.5 text-[#61727D]">
                                {{ $inv->program?->laboratory?->name ?? 'NODIVA Network Laboratory' }}
                            </td>
                            <td class="px-6 py-3.5 text-[#61727D]">
                                {{ $inv->issued_on ? $inv->issued_on->format('M d, Y') : $inv->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-3.5 text-[#61727D]">
                                {{ $inv->due_on ? $inv->due_on->format('M d, Y') : 'Net 30' }}
                            </td>
                            <td class="px-6 py-3.5 font-bold text-[#102A36]">
                                GH₵ {{ number_format((float)$inv->total, 2) }}
                            </td>
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
                                <a href="{{ route('corporate.invoices.show', $inv) }}" class="text-[#167C78] hover:underline font-bold">
                                    View Statement &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-[#61727D]">
                                No program invoices found matching your criteria.
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
