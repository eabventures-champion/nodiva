<x-layouts.workspace title="Compose Communication — NODIVA Operations" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="communications" />
    </x-slot:sidebar>

    <div class="space-y-6 max-w-4xl">
        <!-- Back Navigation & Title -->
        <div class="flex items-center justify-between">
            <a href="{{ route('ops.communications.index') }}" 
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#61727D] hover:text-[#167C78] transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Communication Stream</span>
            </a>
            <span class="text-[11px] font-mono text-[#61727D]">Two-way Institutional Messaging Channel</span>
        </div>

        <div class="bg-white border border-[#D5DEDF] shadow-xs">
            <div class="px-6 py-4 border-b border-[#D5DEDF] bg-[#FAFAFA]">
                <h1 class="text-base font-bold text-[#102A36]">Compose Institutional Communication / Broadcast</h1>
                <p class="text-xs text-[#61727D] mt-0.5">
                    Send a direct formal notification to a specific hospital, laboratory, or corporate institution, or broadcast across partner categories.
                </p>
            </div>

            <form method="POST" action="{{ route('ops.communications.store') }}" class="p-6 space-y-6" x-data="{ recipientType: 'direct' }">
                @csrf

                <!-- Recipient Type & Target Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-[#F5F3ED]/60 border border-[#D5DEDF] rounded-xs">
                    <div>
                        <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                            Recipient Mode <span class="text-[#B42332]">*</span>
                        </label>
                        <select name="recipient_type" x-model="recipientType" 
                                class="w-full text-xs bg-white border border-[#D5DEDF] p-2.5 rounded-xs font-medium text-[#102A36] focus:border-[#167C78] focus:outline-none">
                            <option value="direct">Direct Specific Institution</option>
                            <option value="all_hospitals">Broadcast to All Hospital Administrators</option>
                            <option value="all_laboratories">Broadcast to All Laboratory Administrators</option>
                            <option value="all_corporates">Broadcast to All Corporate Administrators</option>
                            <option value="all_partners">Broadcast to Entire Institutional Network (All Partners)</option>
                        </select>
                        <p class="text-[10px] text-[#61727D] mt-1">
                            Direct messages are private to that organization. Broadcasts deliver simultaneously to all matching administrators.
                        </p>
                    </div>

                    <div x-show="recipientType === 'direct'">
                        <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                            Target Partner Organization <span class="text-[#B42332]">*</span>
                        </label>
                        <select name="recipient_organization_id" 
                                class="w-full text-xs bg-white border border-[#D5DEDF] p-2.5 rounded-xs font-medium text-[#102A36] focus:border-[#167C78] focus:outline-none">
                            <option value="">-- Select Partner Organization --</option>
                            
                            <optgroup label="Hospitals">
                                @foreach ($hospitals as $hosp)
                                    <option value="{{ $hosp->id }}" {{ (string) $hosp->id === (string) $preselectedPartnerId ? 'selected' : '' }}>
                                        🏥 {{ $hosp->name }} ({{ $hosp->city ?? 'Ghana' }})
                                    </option>
                                @endforeach
                            </optgroup>

                            <optgroup label="Diagnostic Laboratories">
                                @foreach ($laboratories as $lab)
                                    <option value="{{ $lab->id }}" {{ (string) $lab->id === (string) $preselectedPartnerId ? 'selected' : '' }}>
                                        🔬 {{ $lab->name }} ({{ $lab->city ?? 'Ghana' }})
                                    </option>
                                @endforeach
                            </optgroup>

                            <optgroup label="Corporate Clients">
                                @foreach ($corporates as $corp)
                                    <option value="{{ $corp->id }}" {{ (string) $corp->id === (string) $preselectedPartnerId ? 'selected' : '' }}>
                                        🏢 {{ $corp->name }} ({{ $corp->city ?? 'Ghana' }})
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                        @error('recipient_organization_id')
                            <p class="text-xs text-[#B42332] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="recipientType !== 'direct'" class="flex items-center">
                        <div class="p-3 bg-blue-50 border border-blue-200 text-blue-800 text-xs rounded-xs flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            <span>This broadcast notice will show up as a high-visibility platform notification to all active administrators in the selected group.</span>
                        </div>
                    </div>
                </div>

                <!-- Priority & Category -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                            Category <span class="text-[#B42332]">*</span>
                        </label>
                        <select name="category" class="w-full text-xs bg-[#F5F3ED] border border-[#D5DEDF] p-2.5 rounded-xs font-medium text-[#102A36] focus:border-[#167C78] focus:outline-none">
                            <option value="general">General Platform Notice</option>
                            <option value="operational">Operational & Logistics Notice</option>
                            <option value="clinical">Clinical & Diagnostic Advisory</option>
                            <option value="billing">Billing & Settlement Advisory</option>
                            <option value="compliance">Accreditation & Qualification Review</option>
                            <option value="urgent_notice">Critical Network Notice</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                            Urgency / Priority <span class="text-[#B42332]">*</span>
                        </label>
                        <select name="priority" class="w-full text-xs bg-[#F5F3ED] border border-[#D5DEDF] p-2.5 rounded-xs font-medium text-[#102A36] focus:border-[#167C78] focus:outline-none">
                            <option value="normal">Normal (Standard Communication)</option>
                            <option value="high">High Priority</option>
                            <option value="urgent">Urgent / Immediate Action Required</option>
                        </select>
                    </div>
                </div>

                <!-- Subject -->
                <div>
                    <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                        Subject / Header <span class="text-[#B42332]">*</span>
                    </label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required placeholder="e.g. Schedule Update for Specimen Dispatch / Updated Accreditations Notice"
                           class="w-full text-xs bg-[#F5F3ED] border border-[#D5DEDF] p-2.5 rounded-xs font-medium text-[#102A36] focus:bg-white focus:border-[#167C78] focus:outline-none">
                    @error('subject')
                        <p class="text-xs text-[#B42332] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Message Body -->
                <div>
                    <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5">
                        Message Content <span class="text-[#B42332]">*</span>
                    </label>
                    <textarea name="message" rows="8" required placeholder="Type the operational notice, directives, or questions to the administrator(s)..."
                              class="w-full text-xs bg-[#F5F3ED] border border-[#D5DEDF] p-3 rounded-xs font-sans text-[#102A36] focus:bg-white focus:border-[#167C78] focus:outline-none leading-relaxed">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="text-xs text-[#B42332] mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-[10px] text-[#61727D] mt-1">
                        Recipients will receive real-time notifications on their workspace and can reply directly to continue this thread.
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#D5DEDF]">
                    <a href="{{ route('ops.communications.index') }}" 
                       class="px-4 py-2 border border-[#D5DEDF] text-xs font-semibold text-[#61727D] hover:bg-[#F5F3ED] rounded-xs transition">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-[#167C78] hover:bg-[#102A36] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition shadow-sm inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span>Dispatch Communication</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.workspace>
