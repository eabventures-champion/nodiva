<x-layouts.workspace title="Laboratory Qualifications — NODIVA Operations" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="qualifications" />
    </x-slot:sidebar>

    <div class="space-y-6" x-data="{ 
        categoriesModalOpen: false, 
        showAddForm: false, 
        editingCategoryId: null 
    }">
        <!-- Top Header & Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-[#102A36]">Laboratory Qualification & License Registry</h1>
                <p class="text-xs text-[#61727D]">Independent verification of laboratory operating licences, ISO accreditations, and regulatory compliance before live referral routing.</p>
            </div>

            <!-- Manage Credential Categories Action Button -->
            <div class="flex items-center gap-2">
                <button type="button" 
                        @click="categoriesModalOpen = true"
                        class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition inline-flex items-center gap-2 shadow-2xs cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Manage Credential Categories</span>
                    <span class="bg-[#102A36] text-[10px] px-2 py-0.5 rounded-full text-emerald-300 font-mono font-bold border border-emerald-400/30">
                        {{ $compulsoryCount }} Compulsory
                    </span>
                </button>
            </div>
        </div>

        <!-- Filter tabs & Counter -->
        <div class="flex flex-wrap items-center justify-between gap-3 bg-white border border-[#D5DEDF] p-3 shadow-2xs">
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                <a href="{{ route('ops.qualifications.index', array_filter(['q' => $searchQuery])) }}" 
                   class="px-3 py-1.5 rounded-sm border {{ empty($currentStatus) ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#E8F0EF]' }}">
                    All Qualifications ({{ $totalCount }})
                </a>
                <a href="{{ route('ops.qualifications.index', array_filter(['status' => 'verified', 'q' => $searchQuery])) }}" 
                   class="px-3 py-1.5 rounded-sm border {{ $currentStatus === 'verified' ? 'bg-[#247451] text-white border-[#247451]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#E8F0EF]' }}">
                    Verified ({{ $verifiedCount }})
                </a>
                <a href="{{ route('ops.qualifications.index', array_filter(['status' => 'pending', 'q' => $searchQuery])) }}" 
                   class="px-3 py-1.5 rounded-sm border {{ $currentStatus === 'pending' ? 'bg-[#94611A] text-white border-[#94611A]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#E8F0EF]' }}">
                    Pending Review ({{ $pendingCount }})
                </a>
                <a href="{{ route('ops.qualifications.index', array_filter(['status' => 'expired', 'q' => $searchQuery])) }}" 
                   class="px-3 py-1.5 rounded-sm border {{ $currentStatus === 'expired' ? 'bg-[#B42332] text-white border-[#B42332]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#E8F0EF]' }}">
                    Expired ({{ $expiredCount }})
                </a>
            </div>

            <div class="text-[11px] text-[#61727D] font-medium flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Enforced policy: Laboratory approval requires <strong>all {{ $compulsoryCount }} compulsory categories</strong> to be verified.</span>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-white border border-[#D5DEDF] p-4 flex flex-col sm:flex-row gap-3 justify-between items-center shadow-2xs">
            <form method="GET" action="{{ route('ops.qualifications.index') }}" class="flex-1 flex gap-2 w-full">
                @if ($currentStatus)
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Search by laboratory name, city, license reference number, or issuing authority..."
                       class="text-xs border border-[#D5DEDF] px-3.5 py-2 focus:border-[#167C78] focus:outline-none bg-white flex-1">
                <button type="submit" class="px-4 py-2 bg-[#102A36] text-white text-xs font-bold hover:bg-[#193B49] transition cursor-pointer">
                    Search
                </button>
                @if ($searchQuery)
                    <a href="{{ route('ops.qualifications.index', array_filter(['status' => $currentStatus])) }}" 
                       class="px-3 py-2 border border-[#D5DEDF] text-[#61727D] hover:bg-[#F5F3ED] text-xs font-bold flex items-center justify-center">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Grouped Qualifications List per Laboratory -->
        <div class="space-y-6">
            @forelse ($laboratories as $lab)
                @php
                    $compStatus = $lab->compulsoryQualificationsStatus();
                    $missingCompulsory = collect($compStatus)->where('is_satisfied', false);
                    $isFullyApproved = $missingCompulsory->isEmpty();
                @endphp
                <div class="bg-white border border-[#D5DEDF] shadow-xs overflow-hidden">
                    <!-- Laboratory Group Header -->
                    <div class="px-6 py-4 bg-[#F5F3ED] border-b border-[#D5DEDF] flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                        <div class="flex items-start sm:items-center gap-3.5">
                            <div class="w-10 h-10 rounded-sm bg-[#102A36] text-white flex items-center justify-center font-bold font-mono text-sm shadow-xs flex-shrink-0 mt-0.5 sm:mt-0">
                                {{ strtoupper(substr($lab->name, 0, 2)) }}
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-sm font-bold text-[#102A36]">{{ $lab->name }}</h2>
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#E8F0EF] text-[#167C78] border border-[#167C78]/20">
                                        {{ $lab->city ?? 'Accra' }}{{ $lab->region ? ', ' . $lab->region->name : '' }}
                                    </span>
                                    @if ($lab->registration_number)
                                        <span class="text-[11px] font-mono text-[#61727D]">
                                            Reg: {{ $lab->registration_number }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-[#61727D] mt-0.5 flex flex-wrap items-center gap-3">
                                    <span>Primary Email: <strong class="text-[#102A36]">{{ $lab->email ?? 'N/A' }}</strong></span>
                                    @if ($lab->phone)
                                        <span>• Phone: <strong class="text-[#102A36]">{{ $lab->phone }}</strong></span>
                                    @endif
                                    @if ($lab->address_line)
                                        <span>• Facility: {{ $lab->address_line }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Operational Qualification Status & Total Badges -->
                        <div class="flex flex-wrap items-center gap-2 self-start lg:self-center">
                            @if ($isFullyApproved)
                                <span class="px-3 py-1.5 text-xs font-bold rounded-sm bg-emerald-50 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1.5 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Fully Qualified for Referrals</span>
                                </span>
                            @else
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-sm bg-amber-50 text-amber-900 border border-amber-300 shadow-2xs text-xs font-semibold"
                                     title="Missing or unverified compulsory credentials: {{ $missingCompulsory->pluck('name')->join(', ') }}">
                                    <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    <span>Missing Compulsory ({{ $missingCompulsory->count() }})</span>
                                </div>
                            @endif

                            <span class="px-3 py-1.5 text-xs font-bold rounded-sm border border-[#167C78]/30 bg-white text-[#167C78] shadow-2xs">
                                {{ $lab->qualifications->count() }} Filed
                            </span>
                        </div>
                    </div>

                    @if (! $isFullyApproved)
                        <!-- Compulsory Checklist Strip for Incomplete Labs -->
                        <div class="px-6 py-2.5 bg-amber-500/10 border-b border-amber-200 text-xs text-[#102A36] flex flex-wrap items-center gap-x-4 gap-y-1">
                            <span class="font-bold text-amber-900 uppercase text-[10px] tracking-wider">Compulsory Requirement Status:</span>
                            @foreach ($compStatus as $st)
                                <span class="inline-flex items-center gap-1 font-medium text-[11px]">
                                    @if ($st['status'] === 'verified')
                                        <span class="text-emerald-700 font-bold">✓ {{ $st['name'] }}</span>
                                    @elseif ($st['status'] === 'pending')
                                        <span class="text-amber-800 font-bold bg-amber-200/60 px-1.5 py-0.5 rounded-2xs">⏳ {{ $st['name'] }} (Pending Verification)</span>
                                    @else
                                        <span class="text-rose-700 font-bold bg-rose-100 px-1.5 py-0.5 rounded-2xs">✕ {{ $st['name'] }} (Not Uploaded)</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <!-- Qualifications Table for this Laboratory -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-white border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold text-[10px] tracking-wider">
                                <tr>
                                    <th class="px-6 py-3">Qualification Type</th>
                                    <th class="px-6 py-3">Issuing Body</th>
                                    <th class="px-6 py-3">Reference / Scope</th>
                                    <th class="px-6 py-3">Validity</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Verification Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#D5DEDF]">
                                @forelse ($lab->qualifications as $qual)
                                    <tr class="hover:bg-[#E8F0EF]/30 transition">
                                        <td class="px-6 py-3.5">
                                            <span class="px-2 py-1 text-[11px] font-bold rounded-sm inline-block
                                                @if ($qual->type === 'accreditation') bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/30
                                                @elseif ($qual->type === 'operating_license') bg-[#102A36]/10 text-[#102A36] border border-[#102A36]/30
                                                @elseif ($qual->type === 'quality_assurance') bg-amber-50 text-amber-800 border border-amber-200
                                                @else bg-gray-100 text-gray-800 border border-gray-200 @endif">
                                                {{ $qual->typeLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3.5 text-[#102A36] font-medium">
                                            {{ $qual->issuing_body }}
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <div class="font-mono font-bold text-[#167C78] text-[13px]">{{ $qual->reference_number }}</div>
                                            <div class="text-[11px] text-[#61727D] mt-0.5">{{ $qual->scope ?? 'Comprehensive diagnostic clinical laboratory testing' }}</div>
                                            @if ($qual->document_path)
                                                <a href="{{ route('ops.qualifications.document', $qual) }}" target="_blank"
                                                   class="inline-flex items-center gap-1 mt-1.5 px-2 py-0.5 rounded-xs bg-[#167C78]/10 text-[#167C78] hover:bg-[#167C78] hover:text-white border border-[#167C78]/30 font-bold text-[10px] transition cursor-pointer">
                                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    <span class="truncate max-w-[160px]">View Attached Document ({{ $qual->document_name ?? 'PDF' }})</span>
                                                </a>
                                            @else
                                                <span class="text-[10px] text-amber-700 italic block mt-1">No digital document attached</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <div class="font-medium text-[#102A36]">
                                                Expires: <span class="font-bold">{{ $qual->expires_on?->format('M d, Y') ?? 'Indefinite' }}</span>
                                            </div>
                                            <div class="text-[10px] text-[#61727D] mt-0.5">
                                                Issued: {{ $qual->issued_on?->format('M d, Y') ?? 'N/A' }}
                                                @if ($qual->isExpired())
                                                    <span class="text-red-600 font-bold ml-1">(Expired)</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase rounded-sm inline-block
                                                @if ($qual->status === 'verified') bg-[#247451]/10 text-[#247451] border border-[#247451]/30
                                                @elseif ($qual->status === 'pending') bg-[#94611A]/10 text-[#94611A] border border-[#94611A]/30
                                                @else bg-[#B42332]/10 text-[#B42332] border border-[#B42332]/30 @endif">
                                                {{ $qual->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3.5 text-right font-medium">
                                            @if ($qual->status === 'pending')
                                                <div class="inline-flex items-center gap-1.5">
                                                    <form method="POST" action="{{ route('ops.qualifications.verify', $qual) }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="action" value="verified">
                                                        <button type="submit" class="px-2.5 py-1 bg-[#247451] hover:bg-[#1b593e] text-white text-[10px] font-bold uppercase tracking-wider rounded-xs transition cursor-pointer">
                                                            Verify
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('ops.qualifications.verify', $qual) }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="action" value="rejected">
                                                        <button type="submit" class="px-2.5 py-1 bg-[#B42332] hover:bg-[#8f1c28] text-white text-[10px] font-bold uppercase tracking-wider rounded-xs transition cursor-pointer">
                                                            Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif ($qual->status === 'verified')
                                                <div class="text-[11px] text-[#247451] font-semibold flex items-center justify-end gap-1">
                                                    <span>✓</span>
                                                    <span>Verified by {{ $qual->verifier?->name ?? 'Platform Operations' }}</span>
                                                </div>
                                                @if ($qual->verified_at)
                                                    <div class="text-[10px] text-[#61727D]">{{ $qual->verified_at->format('M d, Y') }}</div>
                                                @endif
                                            @else
                                                <span class="text-[11px] text-[#B42332] font-semibold">Rejected / Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-6 text-center text-[#61727D] italic">
                                            No qualifications matching the selected criteria on file for this laboratory.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="bg-white border border-[#D5DEDF] p-12 text-center text-[#61727D]">
                    <div class="text-sm font-bold text-[#102A36] mb-1">No Laboratory Qualifications Found</div>
                    <p class="text-xs">There are no laboratory qualifications matching your current search and filter criteria.</p>
                </div>
            @endforelse
        </div>

        @if ($laboratories->hasPages())
            <div class="p-4 bg-white border border-[#D5DEDF]">
                {{ $laboratories->links() }}
            </div>
        @endif

        <!-- CREDENTIAL CATEGORIES MANAGEMENT MODAL -->
        <div x-show="categoriesModalOpen" 
             x-cloak
             style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 overflow-y-auto" 
             aria-labelledby="modal-title" 
             role="dialog" 
             aria-modal="true"
             @keydown.escape.window="categoriesModalOpen = false">
            <div @click.away="categoriesModalOpen = false"
                 class="relative bg-white border border-[#D5DEDF] max-w-4xl w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto rounded-xs my-auto text-left">
                
                <!-- Modal Header -->
                <div class="flex items-start justify-between pb-4 border-b border-[#D5DEDF]">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-bold text-[#102A36]" id="modal-title">
                                Laboratory Credential Categories & Approval Policy
                            </h3>
                            <span class="bg-[#167C78]/10 text-[#167C78] text-[11px] font-bold px-2 py-0.5 rounded-xs border border-[#167C78]/30">
                                {{ $categories->count() }} Categories
                            </span>
                        </div>
                        <p class="text-xs text-[#61727D] mt-1 max-w-2xl">
                            Configure the credential types laboratories can upload. Categories flagged as <strong>Compulsory for Approval</strong> are strictly mandatory: laboratories cannot accept referrals or operate until an authorized document for that category is verified by Operations.
                        </p>
                    </div>
                    <button type="button" 
                            @click="categoriesModalOpen = false"
                            class="text-[#61727D] hover:text-[#102A36] p-1 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                    <!-- Category Management Action Bar -->
                    <div class="py-4 flex items-center justify-between">
                        <div class="text-xs text-[#102A36] font-semibold flex items-center gap-2">
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span>Current Policy: <strong>{{ $compulsoryCount }} Mandatory Categories</strong> required for referral operations</span>
                        </div>
                        <button type="button" 
                                @click="showAddForm = !showAddForm"
                                class="px-3 py-1.5 bg-[#102A36] hover:bg-[#193B49] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span x-text="showAddForm ? 'Close Add Form' : 'Add Credential Category'"></span>
                        </button>
                    </div>

                    <!-- Add Category Sub-Form -->
                    <div x-show="showAddForm" 
                         x-cloak 
                         class="mb-6 p-4 bg-[#F5F3ED] border border-[#167C78]/30 rounded-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-[#D5DEDF] pb-2">
                            <h4 class="text-xs font-bold text-[#102A36] uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Create New Credential Category</span>
                            </h4>
                            <button type="button" @click="showAddForm = false" class="text-xs text-[#61727D] hover:text-[#102A36] cursor-pointer">Cancel</button>
                        </div>

                        <form method="POST" action="{{ route('ops.qualifications.categories.store') }}" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold text-[#102A36] uppercase tracking-wider mb-1">
                                        Category Display Name <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" name="name" required placeholder="e.g. Radiation Safety & Protection License"
                                           class="w-full text-xs border border-[#D5DEDF] px-3 py-2 bg-white focus:border-[#167C78] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-[#102A36] uppercase tracking-wider mb-1">
                                        System Code / Slug <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" name="code" required placeholder="e.g. radiation_safety"
                                           class="w-full text-xs font-mono border border-[#D5DEDF] px-3 py-2 bg-white focus:border-[#167C78] focus:outline-none">
                                    <p class="text-[10px] text-[#61727D] mt-0.5">Unique identifier (letters, numbers, underscores).</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold text-[#102A36] uppercase tracking-wider mb-1">
                                        Issuing Authority Hint
                                    </label>
                                    <input type="text" name="issuing_authority_hint" placeholder="e.g. Nuclear Regulatory Authority (NRA)"
                                           class="w-full text-xs border border-[#D5DEDF] px-3 py-2 bg-white focus:border-[#167C78] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-[#102A36] uppercase tracking-wider mb-1">
                                        Description & Scope Guideline
                                    </label>
                                    <input type="text" name="description" placeholder="e.g. Mandatory for facilities operating diagnostic imaging or radiation equipment."
                                           class="w-full text-xs border border-[#D5DEDF] px-3 py-2 bg-white focus:border-[#167C78] focus:outline-none">
                                </div>
                            </div>

                            <div class="p-3 bg-white border border-[#D5DEDF] rounded-xs flex items-start gap-3">
                                <input type="checkbox" name="is_compulsory" id="new_is_compulsory" value="1"
                                       class="mt-0.5 w-4 h-4 text-[#167C78] rounded-xs border-gray-300 focus:ring-[#167C78] cursor-pointer">
                                <label for="new_is_compulsory" class="text-xs text-[#102A36] cursor-pointer">
                                    <strong class="font-bold block">Compulsory for Platform Approval (Mandatory Requirement)</strong>
                                    <span class="text-[#61727D] text-[11px]">When checked, every partner laboratory must upload and receive operations approval for this credential category before it is eligible to accept diagnostic referrals.</span>
                                </label>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="showAddForm = false" class="px-4 py-2 border border-[#D5DEDF] text-xs font-bold text-[#61727D] hover:bg-white cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition cursor-pointer">
                                    Save Credential Category
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Existing Categories Table -->
                    <div class="border border-[#D5DEDF] overflow-hidden max-h-[55vh] overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold text-[10px] tracking-wider sticky top-0">
                                <tr>
                                    <th class="px-4 py-3">Category Name & Code</th>
                                    <th class="px-4 py-3">Issuing Authority / Scope</th>
                                    <th class="px-4 py-3 text-center">Approval Policy</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#D5DEDF]">
                                @foreach ($categories as $cat)
                                    <tr class="hover:bg-[#E8F0EF]/30 transition" x-data="{ editing: false }">
                                        <!-- Normal View -->
                                        <td class="px-4 py-3.5 align-top">
                                            <div class="font-bold text-[#102A36]">{{ $cat->name }}</div>
                                            <div class="font-mono text-[10px] text-[#61727D] mt-0.5">code: <span class="text-[#167C78] font-semibold">{{ $cat->code }}</span></div>
                                            @if (! $cat->is_active)
                                                <span class="inline-block mt-1 px-1.5 py-0.5 text-[9px] font-bold uppercase bg-gray-200 text-gray-700 rounded-2xs">
                                                    Disabled
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 align-top">
                                            <div class="text-[#102A36] font-medium">{{ $cat->issuing_authority_hint ?? 'Any Accredited Regulator' }}</div>
                                            <div class="text-[11px] text-[#61727D] mt-0.5">{{ $cat->description ?? 'Standard institutional qualification document' }}</div>
                                        </td>
                                        <td class="px-4 py-3.5 align-top text-center">
                                            @if ($cat->is_compulsory)
                                                <div class="inline-flex flex-col items-center gap-1">
                                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-sm bg-emerald-50 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1 shadow-2xs">
                                                        <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                        <span>Compulsory</span>
                                                    </span>
                                                    <span class="text-[9px] text-[#61727D]">Mandatory for live referrals</span>
                                                </div>
                                            @else
                                                <div class="inline-flex flex-col items-center gap-1">
                                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-sm bg-gray-100 text-gray-700 border border-gray-300">
                                                        Optional / Elective
                                                    </span>
                                                    <span class="text-[9px] text-[#61727D]">Not required for approval</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 align-top text-right">
                                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                                <!-- Toggle Compulsory Button -->
                                                <form method="POST" action="{{ route('ops.qualifications.categories.toggle-compulsory', $cat) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            title="{{ $cat->is_compulsory ? 'Click to make this elective' : 'Click to make this mandatory for lab approval' }}"
                                                            class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-xs border transition cursor-pointer
                                                                {{ $cat->is_compulsory ? 'bg-amber-50 text-amber-900 border-amber-300 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-800 border-emerald-300 hover:bg-emerald-100' }}">
                                                        {{ $cat->is_compulsory ? 'Make Elective' : 'Make Compulsory' }}
                                                    </button>
                                                </form>

                                                <!-- Toggle Active Button -->
                                                <form method="POST" action="{{ route('ops.qualifications.categories.toggle-active', $cat) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="px-2 py-1 text-[10px] font-semibold rounded-xs border border-[#D5DEDF] text-[#61727D] hover:bg-white hover:text-[#102A36] transition cursor-pointer">
                                                        {{ $cat->is_active ? 'Disable' : 'Enable' }}
                                                    </button>
                                                </form>

                                                <!-- Edit Trigger -->
                                                <button type="button" 
                                                        @click="editing = !editing"
                                                        class="px-2 py-1 text-[10px] font-semibold rounded-xs border border-[#167C78]/30 bg-[#167C78]/10 text-[#167C78] hover:bg-[#167C78] hover:text-white transition cursor-pointer">
                                                    Edit
                                                </button>
                                            </div>

                                            <!-- Inline Edit Form Row -->
                                            <div x-show="editing" x-cloak class="mt-3 p-3 bg-[#F5F3ED] border border-[#D5DEDF] text-left">
                                                <form method="POST" action="{{ route('ops.qualifications.categories.update', $cat) }}" class="space-y-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-[#102A36] uppercase">Name</label>
                                                        <input type="text" name="name" value="{{ $cat->name }}" required class="w-full text-xs border border-[#D5DEDF] px-2 py-1 bg-white">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-[#102A36] uppercase">Issuing Authority Hint</label>
                                                        <input type="text" name="issuing_authority_hint" value="{{ $cat->issuing_authority_hint }}" class="w-full text-xs border border-[#D5DEDF] px-2 py-1 bg-white">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-[#102A36] uppercase">Description</label>
                                                        <input type="text" name="description" value="{{ $cat->description }}" class="w-full text-xs border border-[#D5DEDF] px-2 py-1 bg-white">
                                                    </div>
                                                    <div class="flex items-center gap-2 pt-1">
                                                        <input type="checkbox" name="is_compulsory" id="edit_comp_{{ $cat->id }}" value="1" {{ $cat->is_compulsory ? 'checked' : '' }} class="w-3.5 h-3.5">
                                                        <label for="edit_comp_{{ $cat->id }}" class="text-[11px] text-[#102A36] font-semibold">Compulsory for Approval</label>
                                                    </div>
                                                    <div class="flex justify-end gap-1.5 pt-1">
                                                        <button type="button" @click="editing = false" class="px-2 py-1 text-[10px] border border-[#D5DEDF] text-[#61727D] cursor-pointer">Cancel</button>
                                                        <button type="submit" class="px-3 py-1 bg-[#167C78] text-white text-[10px] font-bold uppercase rounded-xs cursor-pointer">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Footer -->
                    <div class="mt-6 flex justify-between items-center pt-4 border-t border-[#D5DEDF]">
                        <span class="text-[11px] text-[#61727D]">
                            Changes to compulsory status immediately update referral eligibility and the lab qualification audit checklist.
                        </span>
                        <button type="button" 
                                @click="categoriesModalOpen = false"
                                class="px-4 py-2 bg-[#102A36] hover:bg-[#193B49] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition cursor-pointer">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
</x-layouts.workspace>