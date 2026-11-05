<x-layouts.workspace title="Compose Communication — Laboratory Portal" :organization="$organization">
    <x-slot:sidebar>
        <x-lab-sidebar active="communications" :organization="$organization" />
    </x-slot:sidebar>

    <div class="space-y-6 max-w-4xl">
        <div class="flex items-center justify-between">
            <a href="{{ route('lab.communications.index') }}" 
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#61727D] hover:text-[#167C78] transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Communications</span>
            </a>
            <span class="text-[11px] font-mono text-[#61727D]">Two-way Institutional Communication</span>
        </div>

        <div class="bg-white border border-[#D5DEDF] shadow-xs">
            <div class="px-6 py-4 border-b border-[#D5DEDF] bg-[#FAFAFA]">
                <h1 class="text-base font-bold text-[#102A36]">Compose Institutional Communication</h1>
                <p class="text-xs text-[#61727D] mt-0.5">
                    Directly message referring Hospitals, Corporate screening clients, or the central NODIVA Operations team.
                </p>
            </div>

            <form method="POST" action="{{ route('lab.communications.store') }}" class="p-6 space-y-6">
                @csrf

                <!-- Recipient Organization Selector -->
                <div class="p-4 bg-[#F5F3ED]/60 border border-[#D5DEDF] rounded-xs">
                    <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                        Target Recipient Organization <span class="text-[#B42332]">*</span>
                    </label>
                    <select name="recipient_organization_id" required
                            class="w-full text-xs bg-white border border-[#D5DEDF] p-2.5 rounded-xs font-medium text-[#102A36] focus:border-[#167C78] focus:outline-none">
                        <option value="">-- Select Recipient Institution --</option>
                        
                        @if ($platformOrg)
                            <optgroup label="Central Network Operations">
                                <option value="{{ $platformOrg->id }}" {{ (string) $platformOrg->id === (string) $preselectedPartnerId ? 'selected' : '' }}>
                                    🛡️ NODIVA Operations Network (Central Ops)
                                </option>
                            </optgroup>
                        @endif

                        @if ($hospitals->isNotEmpty())
                            <optgroup label="Connected Hospitals & Clinics">
                                @foreach ($hospitals as $hosp)
                                    <option value="{{ $hosp->id }}" {{ (string) $hosp->id === (string) $preselectedPartnerId ? 'selected' : '' }}>
                                        🏥 {{ $hosp->name }} ({{ $hosp->city ?? 'Hospital' }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif

                        @if ($corporates->isNotEmpty())
                            <optgroup label="Corporate Screening Clients">
                                @foreach ($corporates as $corp)
                                    <option value="{{ $corp->id }}" {{ (string) $corp->id === (string) $preselectedPartnerId ? 'selected' : '' }}>
                                        🏢 {{ $corp->name }} ({{ $corp->city ?? 'Corporate Client' }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif

                        @if ($laboratories->isNotEmpty())
                            <optgroup label="Peer Laboratories">
                                @foreach ($laboratories as $otherLab)
                                    <option value="{{ $otherLab->id }}" {{ (string) $otherLab->id === (string) $preselectedPartnerId ? 'selected' : '' }}>
                                        🔬 {{ $otherLab->name }} ({{ $otherLab->city ?? 'Lab' }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    @error('recipient_organization_id')
                        <p class="text-xs text-[#B42332] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                            Category <span class="text-[#B42332]">*</span>
                        </label>
                        <select name="category" class="w-full text-xs bg-[#F5F3ED] border border-[#D5DEDF] p-2.5 rounded-xs font-medium text-[#102A36] focus:border-[#167C78] focus:outline-none">
                            <option value="general">General Support & Inquiry</option>
                            <option value="operational">Specimen Reception & Accessioning</option>
                            <option value="clinical">Analytical Signoff & Pathologist Review</option>
                            <option value="compliance">Licensing & Accreditation Verification</option>
                            <option value="billing">Settlement Statements & Reconciliation</option>
                            <option value="urgent_notice">Critical Operational Escalation</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                            Priority <span class="text-[#B42332]">*</span>
                        </label>
                        <select name="priority" class="w-full text-xs bg-[#F5F3ED] border border-[#D5DEDF] p-2.5 rounded-xs font-medium text-[#102A36] focus:border-[#167C78] focus:outline-none">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                        Subject <span class="text-[#B42332]">*</span>
                    </label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required placeholder="e.g. Courier Inbound Schedule Query / Specimen Quality Follow-up"
                           class="w-full text-xs bg-[#F5F3ED] border border-[#D5DEDF] p-2.5 rounded-xs font-medium text-[#102A36] focus:bg-white focus:border-[#167C78] focus:outline-none">
                    @error('subject')
                        <p class="text-xs text-[#B42332] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                        Message Content <span class="text-[#B42332]">*</span>
                    </label>
                    <textarea name="message" rows="7" required placeholder="Provide details, specimen barcode or referral order reference if applicable..."
                              class="w-full text-xs bg-[#F5F3ED] border border-[#D5DEDF] p-3 rounded-xs font-sans text-[#102A36] focus:bg-white focus:border-[#167C78] focus:outline-none leading-relaxed">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="text-xs text-[#B42332] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#D5DEDF]">
                    <a href="{{ route('lab.communications.index') }}" 
                       class="px-4 py-2 border border-[#D5DEDF] text-xs font-semibold text-[#61727D] hover:bg-[#F5F3ED] rounded-xs transition">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-[#167C78] hover:bg-[#102A36] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition shadow-sm inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span>Dispatch Message</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.workspace>
