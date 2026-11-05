<x-layouts.workspace title="Laboratory Portal — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-lab-sidebar active="dashboard" />
    </x-slot:sidebar>

    <div class="space-y-2">
        <h1 class="text-2xl font-bold text-[#102A36]">Laboratory Intake & Fulfillment</h1>
        <p class="text-xs text-[#61727D]">Analytical order intake, specimen receipt verification, and result release.</p>
    </div>

    @if (! $organization->hasVerifiedQualifications())
        <!-- Unverified Licensing & Accreditation Prompt Banner -->
        <div class="p-4 bg-amber-50 border-2 border-amber-300 text-xs text-amber-900 rounded-sm shadow-sm flex flex-col md:flex-row justify-between md:items-center gap-3">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <div class="font-bold uppercase tracking-wider text-amber-900 flex items-center gap-2">
                        <span>Laboratory Licensing & Accreditation Verification Required</span>
                        <span class="px-1.5 py-0.2 bg-amber-200 text-amber-900 text-[10px] rounded-sm font-mono">Operations Audit Pending</span>
                    </div>
                    <p class="text-[11px] text-amber-800 mt-0.5 leading-relaxed">
                        Under clinical governance regulations, your laboratory must upload an official operating licence (HEFRA / AHPC) and accreditation (ISO 15189) verified by the Platform Administrator before your facility is eligible to receive patient referrals from partner hospitals.
                    </p>
                </div>
            </div>
            <a href="{{ route('lab.qualifications.index') }}" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider rounded-sm shrink-0 inline-flex items-center gap-1.5 transition shadow-sm self-start md:self-auto cursor-pointer">
                <span>Upload Credentials &rarr;</span>
            </a>
        </div>
    @endif

    @if (($catalogueCount ?? 0) === 0)
        <!-- Incomplete Diagnostic Menu Prompt Banner -->
        <div class="p-4 bg-amber-50 border-2 border-amber-300 text-xs text-amber-900 rounded-sm shadow-sm flex flex-col md:flex-row justify-between md:items-center gap-3">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>
                    <div class="font-bold uppercase tracking-wider text-amber-900 flex items-center gap-2">
                        <span>Laboratory Diagnostic Menu & Capacity Setup Needed</span>
                        <span class="px-1.5 py-0.2 bg-amber-200 text-amber-900 text-[10px] rounded-sm font-mono">0 tests configured</span>
                    </div>
                    <p class="text-[11px] text-amber-800 mt-0.5 leading-relaxed">
                        Your laboratory currently has <strong>0 diagnostic investigations</strong> in your active menu. Referring hospitals and the automated routing engine cannot dispatch referral requests to your facility until you configure your contracted test prices, internal lab codes, and turnaround time benchmarks.
                    </p>
                </div>
            </div>
            <a href="{{ route('lab.catalogue.index') }}" class="px-4 py-2 bg-[#94611A] hover:bg-[#7A5015] text-white text-xs font-bold uppercase tracking-wider rounded-sm shrink-0 inline-flex items-center gap-1.5 transition shadow-sm self-start md:self-auto">
                <span>Configure Diagnostic Menu &rarr;</span>
            </a>
        </div>
    @endif

    <!-- Stat Metric Boxes -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <a href="{{ route('lab.reception.index') }}" class="bg-white p-4 border {{ $counts['awaiting_acceptance'] > 0 ? 'border-[#94611A] bg-[#94611A]/5' : 'border-[#D5DEDF]' }} hover:border-[#167C78] transition block group">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Awaiting Acceptance</span>
                @if ($counts['awaiting_acceptance'] > 0)
                    <span class="w-2 h-2 rounded-full bg-[#94611A] animate-pulse"></span>
                @endif
            </div>
            <div class="text-2xl font-bold text-[#94611A] mt-1 group-hover:text-[#167C78] transition">{{ $counts['awaiting_acceptance'] }}</div>
            <div class="text-[10px] text-[#61727D] mt-1">Click to review & claim &rarr;</div>
        </a>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Analytical Processing</div>
            <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $counts['processing'] }}</div>
        </div>
        <a href="{{ route('lab.results.index', ['tab' => 'review']) }}" class="bg-white p-4 border {{ $counts['pending_review'] > 0 ? 'border-[#94611A] bg-[#94611A]/5' : 'border-[#D5DEDF]' }} hover:border-[#167C78] transition block group">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Pending Review & Release</span>
                @if ($counts['pending_review'] > 0)
                    <span class="w-2 h-2 rounded-full bg-[#94611A] animate-pulse"></span>
                @endif
            </div>
            <div class="text-2xl font-bold text-[#94611A] mt-1 group-hover:text-[#167C78] transition">{{ $counts['pending_review'] }}</div>
            <div class="text-[10px] text-[#61727D] mt-1">Click to review & release &rarr;</div>
        </a>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Fulfilled Orders</div>
            <div class="text-2xl font-bold text-[#247451] mt-1">{{ $counts['completed'] }}</div>
        </div>
    </div>

    <!-- Incoming Orders Table -->
    <div class="bg-white border border-[#D5DEDF]">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-[#D5DEDF] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-4">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Assigned Referral Orders</h2>
            <span class="text-[11px] sm:text-xs text-[#61727D]">Fulfillment queue for {{ $organization->name }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Order Ref</th>
                        <th class="px-6 py-3">Referring Hospital</th>
                        <th class="px-6 py-3">Diagnostic Test</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Specimen Identifier</th>
                        <th class="px-6 py-3">TAT Target</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($incomingOrders as $order)
                        @php
                            $isBroadcast = is_null($order->laboratory_id);
                            $isAwaiting = in_array($order->status->value, ['matching', 'awaiting_lab_acceptance']);
                        @endphp
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">{{ $order->reference }}</td>
                            <td class="px-6 py-3.5">
                                <div class="font-semibold text-[#102A36]">{{ $order->requestingOrganization?->name }}</div>
                                <div class="text-[10px] text-[#61727D]">{{ $order->requestingOrganization?->city ?? 'Accra' }}</div>
                            </td>
                            <td class="px-6 py-3.5">{{ $order->items->pluck('test.name')->join(', ') }}</td>
                            <td class="px-6 py-3.5">
                                @if ($isBroadcast)
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase bg-blue-100 text-blue-800 rounded-sm inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                        Open Broadcast (First to Claim)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                        @if ($order->status->value === 'completed') bg-[#247451]/10 text-[#247451]
                                        @elseif ($order->status->value === 'processing') bg-[#167C78]/10 text-[#167C78]
                                        @else bg-[#94611A]/10 text-[#94611A] @endif">
                                        {{ $order->status->label() }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 font-mono text-[#61727D]">{{ $order->specimens->first()?->identifier ?? 'Pending dispatch' }}</td>
                            <td class="px-6 py-3.5 text-[#61727D]">{{ $order->due_at?->diffForHumans() ?? 'Standard' }}</td>
                            <td class="px-6 py-3.5 text-right font-medium">
                                @if ($isAwaiting)
                                    <a href="{{ route('lab.reception.index') }}" class="px-3 py-1.5 {{ $isBroadcast ? 'bg-[#102A36] hover:bg-[#193B49]' : 'bg-[#167C78] hover:bg-[#136663]' }} text-white text-[11px] font-bold uppercase tracking-wider rounded-sm transition shadow-sm inline-flex items-center gap-1">
                                        <span>{{ $isBroadcast ? 'Review & Claim' : 'Accept Order' }}</span>
                                        <span>&rarr;</span>
                                    </a>
                                @elseif ($order->status->value === 'awaiting_result_review')
                                    <a href="{{ route('lab.results.create', $order) }}" class="px-3 py-1.5 bg-[#94611A] hover:bg-[#7A5015] text-white text-[11px] font-bold uppercase tracking-wider rounded-sm transition shadow-sm inline-flex items-center gap-1">
                                        <span>Review & Release</span>
                                        <span>&rarr;</span>
                                    </a>
                                @elseif ($order->status->value === 'completed')
                                    <a href="{{ route('lab.results.create', $order) }}" class="text-[#247451] hover:underline font-bold text-xs inline-flex items-center gap-1">
                                        <span>View Report</span>
                                        <span>&rarr;</span>
                                    </a>
                                @else
                                    <a href="{{ route('lab.results.create', $order) }}" class="px-3 py-1.5 bg-[#167C78] hover:bg-[#136663] text-white text-[11px] font-bold uppercase tracking-wider rounded-sm transition shadow-sm inline-flex items-center gap-1">
                                        <span>Enter Results</span>
                                        <span>&rarr;</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-[#61727D]">
                                No pending orders in laboratory queue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.workspace>
