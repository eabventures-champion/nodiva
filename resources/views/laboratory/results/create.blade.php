<x-layouts.workspace :title="($order->status->value === 'awaiting_result_review' ? 'Pathologist Review: ' : 'Enter Results: ') . $order->reference . ' — NODIVA'" :organization="$organization">
    <x-slot:sidebar>
        <x-lab-sidebar active="results" />
    </x-slot:sidebar>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold font-mono text-[#102A36]">{{ $order->reference }}</h1>
                <span class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-sm
                    @if ($order->status->value === 'completed') bg-[#247451]/10 text-[#247451]
                    @elseif ($order->status->value === 'processing') bg-[#167C78]/10 text-[#167C78]
                    @else bg-[#94611A]/10 text-[#94611A] @endif">
                    {{ $order->status->label() }}
                </span>
            </div>
            <p class="text-xs text-[#61727D] mt-1">
                Referring Hospital: <strong class="text-[#102A36]">{{ $order->requestingOrganization?->name }}</strong> • Priority: <strong class="uppercase text-[#102A36]">{{ $order->priority }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('lab.results.index', ['tab' => $order->status->value === 'awaiting_result_review' ? 'review' : ($order->status->value === 'completed' ? 'released' : 'processing')]) }}" class="px-3 py-1.5 border border-[#D5DEDF] bg-white text-xs font-semibold text-[#102A36] hover:bg-[#F5F3ED] transition">
                &larr; Back to Results Queue
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Order Summary Card -->
    <div class="bg-white border border-[#D5DEDF] p-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
        <div>
            <div class="text-[10px] font-bold uppercase text-[#61727D]">Requested Diagnostic Investigation</div>
            <div class="font-bold text-sm text-[#102A36] mt-1">{{ $order->items->pluck('test.name')->join(', ') }}</div>
            <div class="text-[11px] text-[#61727D] mt-0.5">Specialty: {{ $order->items->first()?->test?->specialty?->name }}</div>
        </div>
        <div>
            <div class="text-[10px] font-bold uppercase text-[#61727D]">Clinical Indication</div>
            <div class="text-[#102A36] mt-1 italic font-medium">"{{ $order->clinical_indication }}"</div>
            <div class="text-[11px] text-[#61727D] mt-0.5">Specimen: {{ $order->specimens->first()?->type?->container }} ({{ $order->specimens->first()?->identifier }})</div>
        </div>
        <div>
            <div class="text-[10px] font-bold uppercase text-[#61727D]">Turnaround Target</div>
            <div class="font-bold text-sm text-[#102A36] mt-1">{{ $order->due_at?->format('M d, Y H:i') ?? 'Standard TAT' }}</div>
            <div class="text-[11px] text-[#247451] font-semibold mt-0.5">Dual-Control Workflow: Medical Scientist &rarr; Pathologist</div>
        </div>
    </div>

    @php
        $completedResult = $order->results->firstWhere('status', \App\Domain\Results\Enums\ResultStatus::Released)
            ?? ($order->status->value === 'completed' ? $order->results->first() : null);

        $pendingResult = $order->results->firstWhere('status', \App\Domain\Results\Enums\ResultStatus::PendingReview)
            ?? ($order->status->value === 'awaiting_result_review' ? $order->results->first() : null);

        $draftResult = $order->results->firstWhere('status', \App\Domain\Results\Enums\ResultStatus::Draft);

        $isAuthor = $pendingResult && $pendingResult->entered_by === auth()->id();
        $separationEnforced = config('nodiva.result_separation_of_duties', true) && ! $organization->setting('allow_self_release', false);
        $selfReleaseBlocked = $isAuthor && $separationEnforced;
        $canRelease = $canRelease ?? false;
    @endphp

    @if ($order->status->value === 'completed' && $completedResult)
        <!-- ========================================== -->
        <!-- STATE 1: COMPLETED & RELEASED REPORT       -->
        <!-- ========================================== -->
        <div class="bg-white border border-[#D5DEDF] p-8 space-y-6">
            <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-4">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-700 font-bold text-lg">✓</span>
                    <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Authorized Clinical Laboratory Report</h2>
                </div>
                <div class="text-xs text-[#247451] font-bold">
                    Released by {{ $completedResult->releasedBy?->name ?? 'Consultant Pathologist' }}
                </div>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3">Analyte / Component</th>
                        <th class="px-4 py-3">Observed Value</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3">Reference Range</th>
                        <th class="px-4 py-3">Flag</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @foreach ($completedResult->items as $item)
                        <tr>
                            <td class="px-4 py-3 font-bold text-[#102A36]">{{ $item->analyte }}</td>
                            <td class="px-4 py-3 font-bold font-mono">{{ $item->value }}</td>
                            <td class="px-4 py-3 text-[#61727D]">{{ $item->unit ?? '—' }}</td>
                            <td class="px-4 py-3 text-[#61727D] font-mono">{{ $item->reference_range ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm {{ $item->flag === 'critical' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $item->flag }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($completedResult->interpretation || $completedResult->review_notes)
                <div class="bg-[#F5F3ED] p-4 border border-[#D5DEDF] space-y-1 text-xs">
                    <div class="font-bold text-[#102A36]">Pathologist Clinical Interpretation:</div>
                    <p class="text-[#61727D] leading-relaxed">{{ $completedResult->interpretation ?? $completedResult->review_notes }}</p>
                </div>
            @endif
        </div>

    @elseif (($order->status->value === 'awaiting_result_review' || $pendingResult) && $pendingResult)
        <!-- ========================================== -->
        <!-- STATE 2: PATHOLOGIST REVIEW & RELEASE      -->
        <!-- ========================================== -->
        <div class="space-y-6" x-data="{ editingValues: false }">
            <!-- Review Banner -->
            <div class="p-4 bg-amber-50 border border-amber-300 text-xs text-amber-900 flex flex-col sm:flex-row justify-between sm:items-center gap-3 rounded-sm shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="w-3 h-3 rounded-full bg-amber-600 animate-pulse mt-0.5 shrink-0"></span>
                    <div>
                        <div class="font-bold uppercase tracking-wider text-amber-900">Dual-Control Clinical Verification Required</div>
                        <div class="text-[11px] text-amber-800 mt-0.5">
                            Analytical findings recorded by Medical Laboratory Scientist. In accordance with ISO 15189 / AHPC clinical governance, this diagnostic report requires Consultant Pathologist review and digital release.
                        </div>
                    </div>
                </div>
                <div class="shrink-0 text-right">
                    <span class="px-2.5 py-1 bg-amber-200/80 border border-amber-400 font-mono font-bold text-[10px] text-amber-900 uppercase rounded-sm">
                        Pending Pathologist Sign-Off
                    </span>
                </div>
            </div>

            @if ($pendingResult->is_critical)
                <div class="p-4 bg-red-50 border border-red-300 text-xs text-red-900 flex items-center gap-3 rounded-sm">
                    <svg class="w-5 h-5 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <div>
                        <strong class="font-bold">CRITICAL VALUE ALERT:</strong> This report has been flagged with critical laboratory findings. Authorizing release will immediately dispatch mandatory urgent alerts to the referring clinician.
                    </div>
                </div>
            @endif

            <!-- Recorded Analytical Findings Dossier -->
            <div class="bg-white border border-[#D5DEDF] p-6 space-y-6 shadow-sm">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center pb-3 border-b border-[#D5DEDF] gap-2">
                    <div>
                        <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Recorded Analytical Findings Dossier</h2>
                        <p class="text-[11px] text-[#61727D] mt-0.5">
                            Entered by: <strong class="text-[#102A36]">{{ $pendingResult->enteredBy?->name ?? 'Medical Laboratory Scientist' }}</strong> ({{ $pendingResult->enteredBy?->job_title ?? 'Laboratory Scientist' }}) • Submitted on {{ $pendingResult->created_at?->format('M d, Y \a\t H:i') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="editingValues = !editingValues"
                                class="px-2.5 py-1 text-[11px] font-semibold border border-[#D5DEDF] text-[#61727D] hover:text-[#102A36] hover:bg-[#F5F3ED] transition">
                            <span x-show="!editingValues">&#9998; Amend / Correct Values</span>
                            <span x-show="editingValues">&times; Cancel Editing</span>
                        </button>
                    </div>
                </div>

                <!-- Display Table of Findings -->
                <div x-show="!editingValues" class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Analyte / Component</th>
                                <th class="px-4 py-3">Observed Value</th>
                                <th class="px-4 py-3">Unit</th>
                                <th class="px-4 py-3">Biological Reference Interval</th>
                                <th class="px-4 py-3">Flag Classification</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#D5DEDF]">
                            @forelse ($pendingResult->items as $item)
                                <tr class="hover:bg-[#F5F3ED]/40 transition">
                                    <td class="px-4 py-3 font-bold text-[#102A36]">
                                        <div>{{ $item->analyte }}</div>
                                        @if ($item->loinc_code)
                                            <div class="text-[10px] text-[#61727D] font-mono">LOINC: {{ $item->loinc_code }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 bg-[#102A36]/5 border border-[#102A36]/15 font-mono font-bold text-sm text-[#102A36] rounded-sm">
                                            {{ $item->value }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-[#61727D]">{{ $item->unit ?? '—' }}</td>
                                    <td class="px-4 py-3 font-mono text-[#61727D]">{{ $item->reference_range ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                            @if ($item->flag === 'critical') bg-red-100 text-red-800 border border-red-200
                                            @elseif ($item->flag === 'normal') bg-emerald-100 text-emerald-800 border border-emerald-200
                                            @else bg-amber-100 text-amber-800 border border-amber-200 @endif">
                                            {{ $item->flag }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-[#61727D]">
                                        No analytical items recorded for this result.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($pendingResult->interpretation)
                        <div class="mt-4 p-4 bg-[#F5F3ED] border border-[#D5DEDF] space-y-1 text-xs">
                            <div class="font-bold text-[#102A36] uppercase tracking-wider text-[10px]">Medical Scientist Notes & Analytical Remarks:</div>
                            <p class="text-[#61727D] leading-relaxed italic">"{{ $pendingResult->interpretation }}"</p>
                        </div>
                    @endif
                </div>

                <!-- Optional In-place Edit Form if Amend was clicked -->
                <div x-show="editingValues" x-cloak class="pt-2">
                    <form action="{{ route('lab.results.store', $order) }}" method="POST" class="space-y-4">
                        @csrf
                        @php
                            $pendingItemsMap = $pendingResult->items->keyBy('referral_order_item_id');
                        @endphp
                        @foreach ($order->items as $index => $item)
                            @php
                                $prevItem = $pendingItemsMap->get($item->id);
                            @endphp
                            <div class="p-4 border border-[#D5DEDF] bg-[#F5F3ED]/30 space-y-3">
                                <div class="font-bold text-xs text-[#102A36] flex justify-between">
                                    <span>{{ $item->test->name }}</span>
                                    <span class="font-mono text-[11px] text-[#61727D]">LOINC: {{ $item->test->loinc_code ?? 'N/A' }}</span>
                                </div>
                                <input type="hidden" name="items[{{ $index }}][referral_order_item_id]" value="{{ $item->id }}">
                                <input type="hidden" name="items[{{ $index }}][loinc_code]" value="{{ $item->test->loinc_code }}">
                                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-bold uppercase text-[#61727D] mb-1">Analyte</label>
                                        <input type="text" name="items[{{ $index }}][analyte]" value="{{ old('items.'.$index.'.analyte', $prevItem?->analyte ?? $item->test->name) }}" required class="w-full text-xs border border-[#D5DEDF] p-2 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-[#61727D] mb-1">Observed Value</label>
                                        <input type="text" name="items[{{ $index }}][value]" value="{{ old('items.'.$index.'.value', $prevItem?->value ?? '') }}" required class="w-full text-xs border border-[#D5DEDF] p-2 bg-white font-mono font-bold">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-[#61727D] mb-1">Unit</label>
                                        <input type="text" name="items[{{ $index }}][unit]" value="{{ old('items.'.$index.'.unit', $prevItem?->unit ?? 'IU/mL') }}" class="w-full text-xs border border-[#D5DEDF] p-2 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-[#61727D] mb-1">Ref Range</label>
                                        <input type="text" name="items[{{ $index }}][reference_range]" value="{{ old('items.'.$index.'.reference_range', $prevItem?->reference_range ?? '0.00 - 20.00') }}" class="w-full text-xs border border-[#D5DEDF] p-2 bg-white font-mono">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-[#61727D] mb-1">Flag</label>
                                    <select name="items[{{ $index }}][flag]" class="text-xs border border-[#D5DEDF] p-2 bg-white">
                                        <option value="normal" {{ ($prevItem?->flag ?? 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                                        <option value="low" {{ ($prevItem?->flag ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="high" {{ ($prevItem?->flag ?? '') === 'high' ? 'selected' : '' }}>High</option>
                                        <option value="abnormal" {{ ($prevItem?->flag ?? '') === 'abnormal' ? 'selected' : '' }}>Abnormal</option>
                                        <option value="critical" {{ ($prevItem?->flag ?? '') === 'critical' ? 'selected' : '' }}>CRITICAL</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach

                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button" @click="editingValues = false" class="px-4 py-2 border border-[#D5DEDF] text-xs font-bold text-[#61727D]">Cancel</button>
                            <button type="submit" name="submit_for_review" value="1" class="px-4 py-2 bg-[#167C78] text-white text-xs font-bold uppercase">Update & Keep in Review &rarr;</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Pathologist Sign & Release Box -->
            <div class="bg-white border border-[#D5DEDF] p-6 space-y-4 shadow-sm">
                <div class="border-b border-[#D5DEDF] pb-3">
                    <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Pathologist Result Authorization & Clinical Endorsement</h2>
                    <p class="text-[11px] text-[#61727D] mt-0.5">Authorizing this report completes the order, notifies the referring hospital, and issues the clinical dossier.</p>
                </div>

                @if ($canRelease && ! $selfReleaseBlocked)
                    <form action="{{ route('lab.results.release', $pendingResult) }}" method="POST" class="space-y-4 text-xs">
                        @csrf
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">
                                Pathologist Clinical Interpretation / Release Endorsement <span class="text-red-500">*</span>
                            </label>
                            <textarea name="review_notes" rows="3" required
                                      placeholder="Add clinical commentary, histological confirmation, or diagnostic correlation..."
                                      class="w-full border border-[#D5DEDF] p-3 text-xs focus:border-[#167C78] focus:outline-none">Verified and authorized by Consultant Pathologist in accordance with ISO 15189 accreditation standards.</textarea>
                        </div>

                        <div class="p-3 bg-[#E8F0EF] border border-[#167C78]/20 text-[11px] text-[#167C78] flex items-center justify-between">
                            <div>
                                Authorizing Pathologist: <strong class="text-[#102A36]">{{ auth()->user()->name }}</strong> ({{ auth()->user()->job_title ?? 'Senior Pathologist' }})
                            </div>
                            <span class="px-2 py-0.5 bg-[#167C78] text-white text-[9px] font-bold uppercase rounded-sm tracking-wider">
                                ISO 15189 Signatory
                            </span>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="submit" class="px-6 py-3 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition shadow-sm inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Sign & Release Authorized Diagnostic Report &rarr;</span>
                            </button>
                        </div>
                    </form>
                @elseif ($canRelease && $selfReleaseBlocked)
                    <div class="p-4 bg-amber-50 border border-amber-300 text-xs text-amber-900 rounded-sm">
                        <strong class="font-bold">Dual-Control Segregation of Duties Enforced:</strong>
                        <p class="mt-1 text-[11px]">
                            You entered these analytical results into the system. Under ISO 15189 and Allied Health Professions Council (AHPC) standards, an independent accredited Consultant Pathologist (e.g. Prof. Kwame Boateng or Dr. Owusu Bempah) must perform the clinical review and sign-off.
                        </p>
                    </div>
                @else
                    <div class="p-4 bg-[#F5F3ED] border border-[#D5DEDF] text-xs text-[#61727D] rounded-sm">
                        <strong class="font-bold text-[#102A36]">Awaiting Pathologist Authorization:</strong>
                        <p class="mt-1 text-[11px]">
                            Your user account is authorized for analytical processing. A Consultant Pathologist or Laboratory Director will review these recorded findings and issue the final cryptographic release.
                        </p>
                    </div>
                @endif
            </div>
        </div>

    @else
        <!-- ========================================== -->
        <!-- STATE 3: FRESH ENTRY OR CONTINUING DRAFT   -->
        <!-- ========================================== -->
        @php
            $draftItems = $draftResult ? $draftResult->items->keyBy('referral_order_item_id') : collect();
        @endphp

        @if ($draftResult)
            <div class="p-4 bg-amber-50 border border-amber-200 text-xs text-amber-900 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-600 animate-pulse"></span>
                    <span><strong>Draft Result Loaded:</strong> Previously saved analytical values have been pre-filled below. You can continue editing or submit for pathologist review.</span>
                </div>
                <span class="text-[11px] text-amber-700 font-mono">Last saved {{ $draftResult->updated_at->diffForHumans() }}</span>
            </div>
        @endif

        <!-- Result Entry Form -->
        <form action="{{ route('lab.results.store', $order) }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white border border-[#D5DEDF] p-6 space-y-6">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Analytical Values & Reference Ranges</h2>
                    <span class="text-xs text-[#61727D]">All entered values are encrypted at rest</span>
                </div>

                <div class="space-y-4">
                    @foreach ($order->items as $index => $item)
                        @php
                            $draftItem = $draftItems->get($item->id);
                            $defaultAnalyte = old('items.'.$index.'.analyte', $draftItem?->analyte ?? $item->test->name);
                            $defaultValue = old('items.'.$index.'.value', $draftItem?->value ?? '');
                            $defaultUnit = old('items.'.$index.'.unit', $draftItem?->unit ?? 'IU/mL');
                            $defaultRange = old('items.'.$index.'.reference_range', $draftItem?->reference_range ?? '0.00 - 20.00');
                            $defaultFlag = old('items.'.$index.'.flag', $draftItem?->flag ?? 'normal');
                        @endphp
                        <div class="p-4 border border-[#D5DEDF] bg-[#F5F3ED]/30 space-y-4">
                            <div class="font-bold text-xs text-[#102A36] flex justify-between items-center">
                                <span>{{ $item->test->name }} ({{ $item->test->code }})</span>
                                <span class="font-mono text-[11px] text-[#61727D]">LOINC: {{ $item->test->loinc_code ?? 'N/A' }}</span>
                            </div>

                            <input type="hidden" name="items[{{ $index }}][referral_order_item_id]" value="{{ $item->id }}">
                            <input type="hidden" name="items[{{ $index }}][loinc_code]" value="{{ $item->test->loinc_code }}">

                            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-[#102A36] mb-1">Analyte Component Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="items[{{ $index }}][analyte]" value="{{ $defaultAnalyte }}" required
                                           class="w-full text-xs border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-[#102A36] mb-1">Observed Value <span class="text-red-500">*</span></label>
                                    <input type="text" name="items[{{ $index }}][value]" value="{{ $defaultValue }}" required placeholder="e.g. 28.5"
                                           class="w-full text-xs border border-[#D5DEDF] p-2 bg-white font-mono font-bold focus:border-[#167C78] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-[#102A36] mb-1">Unit of Measure</label>
                                    <input type="text" name="items[{{ $index }}][unit]" value="{{ $defaultUnit }}" placeholder="e.g. IU/mL"
                                           class="w-full text-xs border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-[#102A36] mb-1">Reference Range</label>
                                    <input type="text" name="items[{{ $index }}][reference_range]" value="{{ $defaultRange }}" placeholder="0.00 - 20.00"
                                           class="w-full text-xs border border-[#D5DEDF] p-2 bg-white font-mono focus:border-[#167C78] focus:outline-none">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label class="block text-[11px] font-bold text-[#102A36] mb-1">Flag Classification</label>
                                    <select name="items[{{ $index }}][flag]" class="w-full text-xs border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                                        <option value="normal" {{ $defaultFlag === 'normal' ? 'selected' : '' }}>Normal (Within reference interval)</option>
                                        <option value="low" {{ $defaultFlag === 'low' ? 'selected' : '' }}>Low (Below reference interval)</option>
                                        <option value="high" {{ $defaultFlag === 'high' ? 'selected' : '' }}>High (Above reference interval)</option>
                                        <option value="abnormal" {{ $defaultFlag === 'abnormal' ? 'selected' : '' }}>Abnormal (Qualitative positive/reactive)</option>
                                        <option value="critical" {{ $defaultFlag === 'critical' ? 'selected' : '' }}>CRITICAL (Immediate physician alert required)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Clinical Interpretation Notes -->
                <div class="space-y-2 pt-4 border-t border-[#D5DEDF]">
                    <label class="block text-xs font-bold text-[#102A36]">Analytical Interpretation & Pathologist Comments</label>
                    <textarea name="interpretation" rows="3" placeholder="Clinical notes, assay methodology (e.g. Abbott RealTime PCR), analytical sensitivity, and remarks..."
                              class="w-full text-xs border border-[#D5DEDF] p-3 focus:border-[#167C78] focus:outline-none">{{ old('interpretation', $draftResult?->interpretation) }}</textarea>
                </div>

                <!-- Critical Value Alert Checkbox -->
                <div class="p-3 bg-amber-50 border border-amber-200 text-xs">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_critical" value="1" {{ old('is_critical', $draftResult?->is_critical) ? 'checked' : '' }} class="text-amber-600 focus:ring-amber-500 rounded-sm">
                        <span class="font-bold text-amber-900">Mark as Critical Result: Triggers mandatory physician acknowledgement alert.</span>
                    </label>
                </div>

                <!-- Submission Actions -->
                <div class="pt-4 border-t border-[#D5DEDF] flex flex-col sm:flex-row justify-end gap-3">
                    <button type="submit" name="submit_for_review" value="0"
                            class="px-5 py-2.5 border border-[#D5DEDF] text-xs font-bold text-[#102A36] hover:bg-[#F5F3ED] uppercase tracking-wider transition">
                        Save Draft Result
                    </button>
                    <button type="submit" name="submit_for_review" value="1"
                            class="px-5 py-2.5 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition shadow-sm">
                        Submit for Pathologist Review &rarr;
                    </button>
                </div>
            </div>
        </form>
    @endif
</x-layouts.workspace>
