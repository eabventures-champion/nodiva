<x-layouts.workspace title="Licensing & Qualifications — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-lab-sidebar active="qualifications" />
    </x-slot:sidebar>

    <div x-data="{ addModalOpen: false }">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#102A36]">Laboratory Accreditations & Licensing</h1>
                <p class="text-xs text-[#61727D]">Regulator licenses, ISO 15189 certifications, and scopes verified by NODIVA Operations Network.</p>
            </div>
            <button @click="addModalOpen = true" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition shadow-sm">
                + Upload New Qualification
            </button>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                <div class="font-bold">Please correct the following errors:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Total Credentials</div>
                <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $metrics['total'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Verified & Active</div>
                <div class="text-2xl font-bold text-[#247451] mt-1">{{ $metrics['verified'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Pending Operations Audit</div>
                <div class="text-2xl font-bold text-[#94611A] mt-1">{{ $metrics['pending'] }}</div>
            </div>
        </div>

        @if (!empty($compulsoryStatus))
            <!-- Regulatory Approval Checklist -->
            <div class="bg-white border border-[#D5DEDF] p-4 space-y-2.5 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 border-b border-[#D5DEDF]/60 pb-2">
                    <h3 class="text-xs font-bold text-[#102A36] uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Platform Regulatory Approval Requirements</span>
                    </h3>
                    <span class="text-[11px] text-[#61727D]">
                        All compulsory credentials must be verified by Platform Operations before live referrals unlock
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 pt-1">
                    @foreach ($compulsoryStatus as $req)
                        <div class="p-2.5 rounded-xs border text-xs flex items-center justify-between gap-2 transition
                            @if ($req['status'] === 'verified') bg-emerald-50/70 border-emerald-200 text-emerald-900
                            @elseif ($req['status'] === 'pending') bg-amber-50/70 border-amber-200 text-amber-900
                            @else bg-rose-50/70 border-rose-200 text-rose-900 @endif">
                            <div class="truncate">
                                <div class="font-bold truncate text-[#102A36]">{{ $req['name'] }}</div>
                                <div class="text-[10px] text-[#61727D]">Compulsory Credential</div>
                            </div>
                            <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded-xs shrink-0
                                @if ($req['status'] === 'verified') bg-emerald-600 text-white
                                @elseif ($req['status'] === 'pending') bg-amber-600 text-white
                                @else bg-rose-600 text-white @endif">
                                {{ $req['status'] === 'verified' ? 'Verified ✓' : ($req['status'] === 'pending' ? 'Audit Pending ⏳' : 'Missing ✕') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Qualifications Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Accreditation & Regulatory Portfolio</h2>
                <span class="text-xs text-[#61727D]">{{ $organization->name }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Credential Type</th>
                            <th class="px-6 py-3">Reference / Licence Number</th>
                            <th class="px-6 py-3">Issuing Authority</th>
                            <th class="px-6 py-3">Accreditation Scope</th>
                            <th class="px-6 py-3">Validity Period</th>
                            <th class="px-6 py-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($qualifications as $q)
                            <tr class="hover:bg-[#E8F0EF]/40 transition">
                                <td class="px-6 py-3.5 font-bold uppercase text-[11px] text-[#102A36]">
                                    {{ str_replace('_', ' ', $q->type) }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="font-mono font-bold text-[#167C78]">{{ $q->reference_number }}</div>
                                    @if ($q->document_path)
                                        <a href="{{ route('lab.qualifications.document', $q) }}" target="_blank" class="inline-flex items-center gap-1 mt-1 text-[11px] font-bold text-[#102A36] hover:text-[#167C78] hover:underline">
                                            <svg class="w-3.5 h-3.5 text-[#167C78] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="truncate max-w-[150px]">{{ $q->document_name ?? 'View Document' }}</span>
                                        </a>
                                    @else
                                        <span class="text-[10px] text-gray-400 italic block mt-0.5">No document attached</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">{{ $q->issuing_body }}</td>
                                <td class="px-6 py-3.5 text-[#61727D] max-w-xs">{{ $q->scope }}</td>
                                <td class="px-6 py-3.5 text-[#61727D]">
                                    {{ $q->issued_on ? $q->issued_on->format('M Y') : 'N/A' }} &rarr; {{ $q->expires_on ? $q->expires_on->format('M Y') : 'Perpetual' }}
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-sm
                                        @if ($q->status === 'verified') bg-[#247451]/10 text-[#247451] border border-[#247451]/30
                                        @elseif ($q->status === 'pending') bg-[#94611A]/10 text-[#94611A] border border-[#94611A]/30
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ $q->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-[#61727D]">
                                    No licensing credentials uploaded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Qualification Modal -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="addModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-lg w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Register Regulatory Credential</h3>
                    <button @click="addModalOpen = false" class="text-gray-400 hover:text-black font-bold">&times;</button>
                </div>

                <form action="{{ route('lab.qualifications.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Credential Category <span class="text-red-500">*</span></label>
                        <select name="type" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->code }}">
                                    {{ $cat->name }} @if ($cat->is_compulsory) [COMPULSORY FOR APPROVAL] @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Licence / Accreditation Number <span class="text-red-500">*</span></label>
                        <input type="text" name="reference_number" placeholder="e.g. ISO-15189-GH-044" required class="w-full border border-[#D5DEDF] p-2 font-mono focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Issuing Regulatory Body <span class="text-red-500">*</span></label>
                        <input type="text" name="issuing_body" placeholder="e.g. Allied Health Professions Council Ghana" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Approved Diagnostic Scope <span class="text-red-500">*</span></label>
                        <input type="text" name="scope" placeholder="e.g. Molecular Diagnostics, Histopathology, Clinical Chemistry" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Official Credential Document (PDF, PNG, JPG) <span class="text-red-500">*</span></label>
                        <input type="file" name="document" accept=".pdf,.png,.jpg,.jpeg" required class="w-full border border-[#D5DEDF] p-2 bg-[#F5F3ED]/30 focus:border-[#167C78] focus:outline-none text-xs">
                        <p class="text-[10px] text-[#61727D] mt-1">Upload scanned copy of regulator license or ISO accreditation schedule for platform verification (Max 10MB).</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Effective Date <span class="text-red-500">*</span></label>
                            <input type="date" name="issued_on" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Expiration Date</label>
                            <input type="date" name="expires_on" class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="addModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                            Submit for Verification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.workspace>
