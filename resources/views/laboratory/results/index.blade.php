<x-layouts.workspace title="Result Entry & Pathologist Release — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-lab-sidebar active="results" />
    </x-slot:sidebar>

    <div x-data="{
        releaseModalOpen: false,
        selectedResultId: '',
        selectedOrderRef: '',
        selectedItems: [],
        reviewNotes: 'Verified and authorized by Consultant Pathologist in accordance with ISO 15189 accreditation standards.'
    }">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#102A36]">Result Entry & Pathologist Release</h1>
                <p class="text-xs text-[#61727D]">Dual-control analytical verification, reference range validation, and pathologist result release.</p>
            </div>
            <div class="text-xs text-[#61727D] font-mono">
                Dual Control Protocol: <strong>Scientist Entry &rarr; Pathologist Release</strong>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded-sm flex items-start gap-2">
                <svg class="w-4 h-4 text-red-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong class="font-bold">Dual-Control Protocol Notice:</strong> {{ session('error') }}
                </div>
            </div>
        @endif

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">In Analytical Processing</div>
                <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $metrics['processing'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Awaiting Pathologist Review</div>
                <div class="text-2xl font-bold text-[#94611A] mt-1">{{ $metrics['review'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Officially Released</div>
                <div class="text-2xl font-bold text-[#247451] mt-1">{{ $metrics['released'] }}</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex gap-2 text-xs font-semibold overflow-x-auto pb-1">
            <a href="{{ route('lab.results.index', ['tab' => 'processing']) }}" 
               class="px-3 py-1.5 border {{ $currentTab === 'processing' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
                Analytical Processing ({{ $metrics['processing'] }})
            </a>
            <a href="{{ route('lab.results.index', ['tab' => 'review']) }}" 
               class="px-3 py-1.5 border {{ $currentTab === 'review' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
                Awaiting Pathologist Review ({{ $metrics['review'] }})
            </a>
            <a href="{{ route('lab.results.index', ['tab' => 'released']) }}" 
               class="px-3 py-1.5 border {{ $currentTab === 'released' ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
                Completed & Released ({{ $metrics['released'] }})
            </a>
        </div>

        @if ($currentTab === 'review' && ! $canRelease)
            <div class="p-4 bg-[#F5F3ED] border-l-4 border-[#94611A] text-xs space-y-2">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-[#94611A] text-white text-[10px] font-bold uppercase rounded-sm">Dual-Control Protocol (ISO 15189)</span>
                    <span class="font-bold text-[#102A36]">Analytical Testing vs. Pathologist Verification Segregation</span>
                </div>
                <p class="text-[#61727D]">
                    You are signed in as <strong class="text-[#102A36]">{{ auth()->user()->name }}</strong> (<em>{{ auth()->user()->job_title }}</em>). Under ISO 15189 accreditation and Allied Health Professions Council (AHPC) standards, Medical Laboratory Scientists record analytical findings, while final clinical authorization and release must be executed by an accredited Consultant Pathologist.
                </p>
                <div class="p-3 bg-white border border-[#D5DEDF] rounded-sm text-[11px] text-[#102A36] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-[#61727D]">Designated Authorizing Pathologist:</span> 
                        <strong class="text-[#102A36]">{{ $pathologist?->name ?? 'Prof. Kwame Boateng, FRCPath' }}</strong>
                        <span class="text-[#61727D]">({{ $pathologist?->email ?? 'pathologist@synlab-gh.example' }})</span>
                    </div>
                    <div class="text-[#167C78] font-bold flex items-center gap-1">
                        Sign in as Consultant Pathologist to verify & release reports &rarr;
                    </div>
                </div>
            </div>
        @endif

        <!-- Orders Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Order Ref</th>
                            <th class="px-6 py-3">Referring Hospital</th>
                            <th class="px-6 py-3">Diagnostic Investigation</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Priority</th>
                            <th class="px-6 py-3">Result Reference</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-[#E8F0EF]/40 transition">
                                <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">{{ $order->reference }}</td>
                                <td class="px-6 py-3.5">{{ $order->requestingOrganization?->name }}</td>
                                <td class="px-6 py-3.5">{{ $order->items->pluck('test.name')->join(', ') }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                        @if ($order->status->value === 'completed') bg-[#247451]/10 text-[#247451]
                                        @elseif ($order->status->value === 'processing') bg-[#167C78]/10 text-[#167C78]
                                        @else bg-[#94611A]/10 text-[#94611A] @endif">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 uppercase font-semibold text-[10px] text-[#61727D]">{{ $order->priority }}</td>
                                <td class="px-6 py-3.5 font-mono text-[#61727D]">
                                    @if ($order->results->isNotEmpty())
                                        Result #{{ $order->results->first()->id }} (v{{ $order->results->first()->version }})
                                    @else
                                        <span class="italic">Pending Entry</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right font-medium">
                                    @if ($order->status->value === 'processing' || $order->status->value === 'specimen_received')
                                        <a href="{{ route('lab.results.create', $order) }}" class="px-3 py-1.5 bg-[#167C78] hover:bg-[#136663] text-white text-[11px] font-bold uppercase transition">
                                            Enter Results &rarr;
                                        </a>
                                    @elseif ($order->status->value === 'awaiting_result_review')
                                        @if ($order->results->isNotEmpty())
                                            @php
                                                $res = $order->results->first();
                                                $isAuthor = $res && $res->entered_by === auth()->id();
                                                $separationEnforced = config('nodiva.result_separation_of_duties', true) && ! $organization->setting('allow_self_release', false);
                                                $selfReleaseBlocked = $isAuthor && $separationEnforced;
                                            @endphp

                                            @if ($canRelease && ! $selfReleaseBlocked)
                                                @php
                                                    $itemsJson = json_encode($res->items->map(fn($it) => [
                                                        'analyte' => $it->analyte,
                                                        'value' => $it->value,
                                                        'unit' => $it->unit ?? '',
                                                        'reference_range' => $it->reference_range ?? 'N/A',
                                                        'flag' => $it->flag,
                                                    ]));
                                                @endphp
                                                <div class="inline-flex items-center gap-2">
                                                    <a href="{{ route('lab.results.create', $order) }}" class="px-2.5 py-1.5 border border-[#D5DEDF] bg-white hover:bg-[#F5F3ED] text-[#102A36] text-[11px] font-bold uppercase transition" title="Inspect Full Dossier">
                                                        Inspect Dossier &rarr;
                                                    </a>
                                                    <button @click="selectedResultId = '{{ $res->id }}'; selectedOrderRef = '{{ $order->reference }}'; selectedItems = {{ $itemsJson }}; releaseModalOpen = true"
                                                            class="px-3 py-1.5 bg-[#167C78] hover:bg-[#136663] text-white text-[11px] font-bold uppercase transition inline-flex items-center gap-1.5 shadow-sm">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        Pathologist Release &rarr;
                                                    </button>
                                                </div>
                                            @elseif ($canRelease && $selfReleaseBlocked)
                                                <div class="inline-flex flex-col items-end">
                                                    <div class="inline-flex items-center gap-2">
                                                        <a href="{{ route('lab.results.create', $order) }}" class="text-[#167C78] hover:underline font-bold text-xs">
                                                            View Dossier &rarr;
                                                        </a>
                                                        <span class="px-2 py-1 bg-amber-50 border border-amber-300 text-amber-800 text-[10px] font-bold uppercase rounded-sm" title="You entered this analytical result. Segregation of duties requires a different authorizer.">
                                                            Self-Release Blocked
                                                        </span>
                                                    </div>
                                                    <span class="text-[9px] text-[#61727D] mt-0.5">Dual-control reviewer required</span>
                                                </div>
                                            @else
                                                <div class="inline-flex flex-col items-end">
                                                    <div class="inline-flex items-center gap-2">
                                                        <a href="{{ route('lab.results.create', $order) }}" class="text-[#167C78] hover:underline font-bold text-xs">
                                                            View Dossier &rarr;
                                                        </a>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-bold uppercase rounded-sm">
                                                            <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                                            Awaiting Sign-Off
                                                        </span>
                                                    </div>
                                                    <span class="text-[9px] text-[#61727D] mt-0.5">
                                                        Signatory: <strong class="text-[#102A36]">{{ $pathologist?->name ?? 'Consultant Pathologist' }}</strong>
                                                    </span>
                                                </div>
                                            @endif
                                        @endif
                                    @elseif ($order->status->value === 'completed')
                                        <a href="{{ route('lab.results.create', $order) }}" class="text-[#167C78] hover:underline font-bold">
                                            View Report &rarr;
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-[#61727D]">
                                    No orders found in this workflow queue.
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

        <!-- Pathologist Authorization & Release Modal -->
        <div x-show="releaseModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="releaseModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-2xl w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Pathologist Result Authorization</h3>
                        <div class="text-[11px] text-[#61727D] mt-0.5">Dual-Control Clinical Verification (ISO 15189 / AHPC Standard)</div>
                    </div>
                    <button @click="releaseModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg leading-none">&times;</button>
                </div>

                <form :action="'/lab/results/' + selectedResultId + '/release'" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <div class="text-[#61727D] mb-2 flex items-center justify-between">
                            <span>Diagnostic Report for Order: <strong class="font-mono text-[#102A36]" x-text="selectedOrderRef"></strong></span>
                            <span class="text-[10px] uppercase font-bold text-amber-700 bg-amber-50 px-2 py-0.5 border border-amber-200 rounded-sm">Pending Verification</span>
                        </div>

                        <!-- Analytical Findings Table inside the modal -->
                        <div class="border border-[#D5DEDF] bg-white rounded-sm overflow-hidden mb-3">
                            <div class="px-3 py-2 bg-[#F5F3ED] border-b border-[#D5DEDF] flex justify-between items-center text-[10px] font-bold uppercase tracking-wider text-[#61727D]">
                                <span>Recorded Analytical Findings</span>
                                <span class="text-[#167C78]">ISO 15189 Dual-Control Verification</span>
                            </div>
                            <div class="max-h-48 overflow-y-auto">
                                <table class="w-full text-left text-[11px]">
                                    <thead class="bg-gray-50 border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold text-[10px]">
                                        <tr>
                                            <th class="px-3 py-2">Analyte / Component</th>
                                            <th class="px-3 py-2">Observed Value</th>
                                            <th class="px-3 py-2">Unit</th>
                                            <th class="px-3 py-2">Reference Range</th>
                                            <th class="px-3 py-2">Flag</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#D5DEDF]">
                                        <template x-for="item in selectedItems" :key="item.analyte">
                                            <tr class="hover:bg-[#F5F3ED]/30">
                                                <td class="px-3 py-2 font-bold text-[#102A36]" x-text="item.analyte"></td>
                                                <td class="px-3 py-2">
                                                    <span class="px-2 py-0.5 bg-[#102A36]/5 border border-[#102A36]/15 font-mono font-bold text-xs text-[#102A36] rounded-sm" x-text="item.value"></span>
                                                </td>
                                                <td class="px-3 py-2 text-[#61727D]" x-text="item.unit || '—'"></td>
                                                <td class="px-3 py-2 text-[#61727D] font-mono" x-text="item.reference_range || 'N/A'"></td>
                                                <td class="px-3 py-2">
                                                    <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded-sm"
                                                          :class="item.flag === 'critical' ? 'bg-red-100 text-red-800 border border-red-200' : (item.flag === 'normal' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200')"
                                                          x-text="item.flag"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <label class="block font-bold text-[#102A36] mb-1">Pathologist Clinical Interpretation / Release Endorsement <span class="text-red-500">*</span></label>
                        <textarea name="review_notes" rows="3" x-model="reviewNotes" required
                                  class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none"></textarea>
                    </div>

                    <div class="p-3 bg-[#E8F0EF] border border-[#167C78]/20 text-[11px] text-[#167C78]">
                        <strong>Clinical Release Note:</strong> Authorizing this report will immediately encrypt the findings, transition the referral order to <strong>Completed</strong>, notify the referring hospital clinician, and trigger the institutional settlement invoice.
                    </div>

                    <div class="p-2.5 bg-[#F5F3ED] border border-[#D5DEDF] text-[11px] text-[#102A36] flex items-center justify-between">
                        <div>
                            Authorizing Pathologist: <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->job_title }})
                        </div>
                        <span class="px-2 py-0.5 bg-[#167C78] text-white text-[9px] font-bold uppercase rounded-sm">
                            ISO 15189 Signatory
                        </span>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="releaseModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                            Sign & Release Result
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.workspace>
