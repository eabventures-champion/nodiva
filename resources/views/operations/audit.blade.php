<x-layouts.workspace title="Security & Audit Trail — NODIVA Operations" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="audit" />
    </x-slot:sidebar>

    <div x-data="{ purgeModalOpen: false, confirmText: '', password: '' }" class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-[#102A36]">Cryptographic Audit Trail & Security</h1>
                <p class="text-xs text-[#61727D]">Immutable, append-only SHA-256 hash-chained security log capturing actor, tenant, action, and timestamp.</p>
            </div>

            <!-- Hash chain verification banner -->
            <div class="flex items-center gap-3">
                @if ($isChainValid)
                    <div class="px-3 py-1.5 bg-[#247451]/10 border border-[#247451]/30 text-xs font-semibold text-[#247451] flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Audit Hash-Chain Intact & Verified</span>
                    </div>
                @else
                    <div class="px-3 py-1.5 bg-[#B42332]/10 border border-[#B42332]/30 text-xs font-semibold text-[#B42332]">
                        Warning: Hash chain discrepancy at record #{{ $brokenRecordId }}
                    </div>
                @endif

                @if (auth()->user()?->isCto())
                    <button @click="purgeModalOpen = true"
                            class="px-3 py-1.5 bg-[#B42332] hover:bg-[#901B27] text-white text-xs font-bold uppercase tracking-wider rounded-sm transition shadow-sm inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Reset / Purge Database</span>
                    </button>
                @endif
            </div>
        </div>


        @if (auth()->user()?->isCto())
            <!-- Database Sanitization & Purge Info Banner -->
            <div id="database-purge" class="bg-white border-2 border-[#B42332]/30 p-6 space-y-4 shadow-sm">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-[#D5DEDF] pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 bg-[#B42332] text-white text-[10px] font-bold uppercase rounded-sm">CTO Restricted</span>
                            <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Platform Database Sanitization & Reset Utility</h2>
                        </div>
                        <p class="text-xs text-[#61727D] mt-1">Clears transactional clinical, logistics, and billing records while strictly preserving platform configuration.</p>
                    </div>
                    <button type="button" @click="purgeModalOpen = true"
                            class="px-4 py-2 bg-[#B42332] hover:bg-[#901B27] text-white text-xs font-bold uppercase tracking-wider rounded-sm transition shadow-sm inline-flex items-center gap-1.5 self-start sm:self-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Execute Platform Purge</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <!-- What is 100% Preserved -->
                    <div class="p-4 bg-emerald-50/60 border border-emerald-200 space-y-2 rounded-sm">
                        <div class="flex items-center gap-1.5 text-emerald-900 font-bold uppercase text-[11px]">
                            <svg class="w-4 h-4 text-emerald-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span>Strictly Preserved (Never Deleted):</span>
                        </div>
                        <ul class="space-y-1.5 text-[#102A36] text-[11px]">
                            <li class="flex items-start gap-1.5">
                                <span class="text-emerald-700 font-bold">1.</span>
                                <span><strong>Master Diagnostic Test Catalogue:</strong> National formulary tests, LOINC interoperability codes, specialties, specimen tubes, and patient pre-analytical instructions.</span>
                            </li>
                            <li class="flex items-start gap-1.5">
                                <span class="text-emerald-700 font-bold">2.</span>
                                <span><strong>NODIVA Operations Network & CTO Credentials:</strong> Platform organization and Chief Technical Officer login (ID #1), encrypted passwords, institutional permissions, and security roles.</span>
                            </li>
                        </ul>
                    </div>

                    <!-- What is Cleared -->
                    <div class="p-4 bg-red-50/60 border border-red-200 space-y-2 rounded-sm">
                        <div class="flex items-center gap-1.5 text-red-900 font-bold uppercase text-[11px]">
                            <svg class="w-4 h-4 text-red-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>Sanitized & Reset to 0:</span>
                        </div>
                        <ul class="space-y-1 text-[#61727D] text-[11px]">
                            <li>• All Referral Orders, status transitions & matching records</li>
                            <li>• All Diagnostic Results, analytical values & pathologist endorsements</li>
                            <li>• All Specimen Containers, barcodes & courier custody events</li>
                            <li>• All Invoices, settlements, ledger transactions & reconciliation runs</li>
                            <li>• All Test Patients, referral consents, and support tickets</li>
                            <li>• <strong>All Registered Partner Organizations</strong> (Hospitals, Laboratories, Corporate clients reset to 0)</li>
                            <li>• <strong>All Partner User Accounts</strong> (Next registered users and partners will sequence cleanly from ID #2)</li>
                            <li>• <strong>Laboratory Diagnostic Menu & Capacity</strong> (Custom lab codes, prices & TATs cleared)</li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Audit Logs Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">System Event Journal</h2>
                <span class="text-xs text-[#61727D]">Redacted context: Zero PII or clinical values stored in logs</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Event Action</th>
                            <th class="px-6 py-3">Actor / Institution</th>
                            <th class="px-6 py-3">Target Entity</th>
                            <th class="px-6 py-3">Outcome</th>
                            <th class="px-6 py-3">Cryptographic Hash</th>
                            <th class="px-6 py-3 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-[#E8F0EF]/40 transition">
                                <td class="px-6 py-3.5">
                                    <span class="font-mono font-bold text-[#102A36]">{{ $log->action }}</span>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="font-semibold text-[#172A35]">{{ $log->actor?->name ?? 'Automated Engine' }}</div>
                                    <div class="text-[10px] text-[#61727D]">{{ $log->actorOrganization?->name ?? 'Platform Core' }}</div>
                                </td>
                                <td class="px-6 py-3.5 text-[#61727D]">
                                    {{ $log->auditable_type ?? 'Network' }} #{{ $log->auditable_id ?? '-' }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm {{ $log->outcome === 'success' ? 'bg-[#247451]/10 text-[#247451]' : 'bg-[#B42332]/10 text-[#B42332]' }}">
                                        {{ $log->outcome }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 font-mono text-[10px] text-[#61727D]" title="{{ $log->hash }}">
                                    {{ substr($log->hash, 0, 20) }}...
                                </td>
                                <td class="px-6 py-3.5 text-right text-[#61727D]">
                                    {{ $log->created_at->format('M d, Y H:i:s') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-[#61727D]">
                                    No audit events recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="px-6 py-3 border-t border-[#D5DEDF] bg-[#F5F3ED]">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        @if (auth()->user()?->isCto())
            <!-- High-Consequence Purge Confirmation Modal -->
            <div x-show="purgeModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
                <div @click.away="purgeModalOpen = false" class="bg-white border-2 border-[#B42332] max-w-lg w-full p-6 space-y-4 shadow-2xl">
                    <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                        <div class="flex items-center gap-2 text-[#B42332]">
                            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-[#102A36]">Confirm Platform Database Purge</h3>
                        </div>
                        <button @click="purgeModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                    </div>

                    <div class="p-3 bg-red-50 border border-red-200 text-xs text-red-900 space-y-1">
                        <div class="font-bold">⚠️ Warning: High-Consequence Platform Action</div>
                        <p class="leading-relaxed">
                            This action will immediately truncate all clinical referral orders, analytical results, custody chains, invoices, and patients. <strong>Master Diagnostic Catalogue, CTO login credentials, and Laboratory Menu entries will be preserved.</strong>
                        </p>
                    </div>

                    <form action="{{ route('ops.system.purge') }}" method="POST" class="space-y-4 text-xs">
                        @csrf
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">
                                Type <span class="font-mono text-[#B42332] font-bold">PURGE</span> to confirm <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="confirmation_code" x-model="confirmText" required placeholder="PURGE"
                                   class="w-full border border-[#D5DEDF] p-2.5 font-mono font-bold focus:border-[#B42332] focus:outline-none uppercase">
                        </div>

                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">
                                CTO Account Password <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="password" x-model="password" required placeholder="Enter your login password"
                                   class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#B42332] focus:outline-none">
                        </div>

                        <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                            <button type="button" @click="purgeModalOpen = false"
                                    class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D] hover:bg-[#F5F3ED]">
                                Cancel
                            </button>
                            <button type="submit"
                                    :disabled="confirmText.trim().toUpperCase() !== 'PURGE' || !password"
                                    class="px-5 py-2.5 bg-[#B42332] hover:bg-[#901B27] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold uppercase tracking-wider transition shadow-sm">
                                Authorize & Purge Database
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.workspace>
