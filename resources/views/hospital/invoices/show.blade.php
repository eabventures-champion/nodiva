<x-layouts.workspace title="Invoice {{ $invoice->number }} — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-hospital-sidebar active="invoices" />
    </x-slot:sidebar>

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold font-mono text-[#102A36]">{{ $invoice->number }}</h1>
                <span class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-sm
                    @if ($invoice->status === 'paid') bg-[#247451]/10 text-[#247451] border border-[#247451]/30
                    @elseif ($invoice->status === 'partially_paid') bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/30
                    @else bg-[#94611A]/10 text-[#94611A] border border-[#94611A]/30 @endif">
                    {{ str_replace('_', ' ', $invoice->status) }}
                </span>
            </div>
            <p class="text-xs text-[#61727D] mt-1">
                Issued on {{ $invoice->issued_on ? $invoice->issued_on->format('M d, Y') : $invoice->created_at->format('M d, Y') }} • Due by {{ $invoice->due_on ? $invoice->due_on->format('M d, Y') : 'Net 30' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3 py-1.5 border border-[#D5DEDF] bg-white text-xs font-semibold text-[#102A36] hover:bg-[#F5F3ED]">
                Print Statement
            </button>
            <a href="{{ route('hospital.invoices.index') }}" class="px-3 py-1.5 bg-[#102A36] text-white text-xs font-semibold hover:bg-[#193B49]">
                &larr; Back to Invoices
            </a>
        </div>
    </div>

    <!-- Printable Invoice Statement Box -->
    <div class="bg-white border border-[#D5DEDF] p-8 space-y-8 max-w-4xl mx-auto shadow-sm">
        <!-- Brand & Partner Header -->
        <div class="flex justify-between items-start border-b border-[#D5DEDF] pb-6">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 bg-[#102A36] flex items-center justify-center font-bold text-white text-xs">N</span>
                    <span class="text-xl font-bold tracking-tight text-[#102A36]">NODIVA</span>
                </div>
                <div class="text-xs text-[#61727D] mt-1">Institutional Diagnostic Network Platform</div>
                <div class="text-[11px] text-[#61727D]">Accra, Greater Accra Region, Ghana</div>
            </div>

            <div class="text-right text-xs space-y-1">
                <div class="text-lg font-bold font-mono text-[#102A36]">{{ $invoice->number }}</div>
                <div class="text-[#61727D]">Date: <strong>{{ $invoice->issued_on ? $invoice->issued_on->format('d M Y') : $invoice->created_at->format('d M Y') }}</strong></div>
                @if ($invoice->order)
                    <div class="text-[#61727D]">Referral Ref: <strong class="font-mono text-[#167C78]">{{ $invoice->order->reference }}</strong></div>
                @endif
            </div>
        </div>

        <!-- Billing Parties -->
        <div class="grid grid-cols-2 gap-8 text-xs">
            <div class="space-y-1">
                <div class="text-[10px] font-bold uppercase text-[#61727D] tracking-wider">Billed To (Requesting Hospital)</div>
                <div class="font-bold text-sm text-[#102A36]">{{ $organization->name }}</div>
                <div class="text-[#61727D]">{{ $organization->email }} • {{ $organization->phone ?? '+233 30 200 0002' }}</div>
                <div class="text-[#61727D]">{{ $organization->city ?? 'Accra' }}, Ghana</div>
            </div>

            <div class="space-y-1 text-right">
                <div class="text-[10px] font-bold uppercase text-[#61727D] tracking-wider">Diagnostic Provider</div>
                <div class="font-bold text-sm text-[#102A36]">{{ $invoice->order?->laboratory?->name ?? 'Verified Diagnostic Laboratory' }}</div>
                <div class="text-[#61727D]">Fulfilled via NODIVA Managed Network</div>
                <div class="text-[#61727D]">ISO 15189 / HEFRA Validated</div>
            </div>
        </div>

        <!-- Itemized Table -->
        <div>
            <table class="w-full text-left text-xs border border-[#D5DEDF]">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3">Description / Diagnostic Investigation</th>
                        <th class="px-4 py-3 text-center">Qty</th>
                        <th class="px-4 py-3 text-right">Unit Price (GHS)</th>
                        <th class="px-4 py-3 text-right">Amount (GHS)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($invoice->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-bold text-[#102A36]">{{ $item->description }}</div>
                                @if ($item->test_code)
                                    <div class="font-mono text-[10px] text-[#61727D]">{{ $item->test_code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right font-mono">GH₵ {{ number_format((float)($item->unit_amount ?? $item->unit_price ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-[#102A36]">GH₵ {{ number_format((float)($item->amount ?? $item->subtotal ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-3 font-bold text-[#102A36]">
                                Specialized Diagnostic Referral Testing ({{ $invoice->order?->reference ?? 'Referral Batch' }})
                            </td>
                            <td class="px-4 py-3 text-center">1</td>
                            <td class="px-4 py-3 text-right font-mono">GH₵ {{ number_format((float)$invoice->subtotal, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-[#102A36]">GH₵ {{ number_format((float)$invoice->subtotal, 2) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Financial Totals Block -->
        <div class="flex justify-end">
            <div class="w-72 space-y-2 text-xs">
                <div class="flex justify-between py-1 text-[#61727D]">
                    <span>Subtotal</span>
                    <span class="font-mono font-bold text-[#102A36]">GH₵ {{ number_format((float)$invoice->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 text-[#61727D]">
                    <span>Taxes / Statutory Levies</span>
                    <span class="font-mono text-[#102A36]">GH₵ {{ number_format((float)$invoice->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-t-2 border-[#102A36] font-bold text-sm text-[#102A36]">
                    <span>Total Invoiced</span>
                    <span class="font-mono">GH₵ {{ number_format((float)$invoice->total, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 text-[#247451] font-semibold">
                    <span>Amount Paid</span>
                    <span class="font-mono">GH₵ {{ number_format((float)$invoice->amount_paid, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-t border-[#D5DEDF] font-bold text-sm {{ (float)$invoice->balance_due > 0 ? 'text-[#94611A]' : 'text-[#247451]' }}">
                    <span>Balance Due</span>
                    <span class="font-mono">GH₵ {{ number_format((float)$invoice->balance_due, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Footer / Remittance Notes -->
        <div class="pt-6 border-t border-[#D5DEDF] text-[11px] text-[#61727D] space-y-1">
            <div class="font-bold text-[#102A36]">Settlement Remittance Information:</div>
            <div>Bank: <strong>Ghana International Bank / Standard Chartered Bank Ghana</strong></div>
            <div>Account Name: <strong>NODIVA Technology Networks Limited (Operating Account)</strong></div>
            <div>Automated Clearing: <strong>GhIPSS / Instant Pay / Mobile Money Settlement Supported</strong></div>
        </div>
    </div>
</x-layouts.workspace>
