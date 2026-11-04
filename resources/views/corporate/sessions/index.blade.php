<x-layouts.workspace title="Appointment Sessions — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-corporate-sidebar active="sessions" />
    </x-slot:sidebar>

    <div x-data="{ sessionModalOpen: false }">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#102A36]">Phlebotomy & Screening Sessions</h1>
                <p class="text-xs text-[#61727D]">Coordinate mobile on-site laboratory phlebotomy camps and diagnostic collection stations.</p>
            </div>
            @if ($programs->isNotEmpty())
                <button @click="sessionModalOpen = true" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition shadow-sm">
                    + Schedule Collection Session
                </button>
            @endif
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                {{ session('success') }}
            </div>
        @endif

        <!-- Metric Stat Boxes -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Total Collection Sessions</div>
                <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $metrics['total_sessions'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Allocated Capacity Slots</div>
                <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $metrics['total_capacity'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Booked Employee Screenings</div>
                <div class="text-2xl font-bold text-[#247451] mt-1">{{ $metrics['total_booked'] }}</div>
            </div>
        </div>

        <!-- Sessions Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Scheduled Collection Sessions</h2>
                <span class="text-xs text-[#61727D]">{{ $sessions->total() }} session(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Screening Program</th>
                            <th class="px-6 py-3">Collection Location</th>
                            <th class="px-6 py-3">Mode</th>
                            <th class="px-6 py-3">Date & Time Range</th>
                            <th class="px-6 py-3">Booked / Capacity</th>
                            <th class="px-6 py-3 text-right">Remaining Slots</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($sessions as $session)
                            <tr class="hover:bg-[#E8F0EF]/40 transition">
                                <td class="px-6 py-3.5 font-bold text-[#102A36]">
                                    {{ $session->program?->name }}
                                </td>
                                <td class="px-6 py-3.5">{{ $session->location }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm {{ $session->mode === 'mobile_onsite' ? 'bg-[#E8F0EF] text-[#167C78]' : 'bg-gray-100 text-gray-700' }}">
                                        {{ str_replace('_', ' ', $session->mode) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-[#61727D]">
                                    {{ $session->starts_at?->format('M d, Y H:i') }} - {{ $session->ends_at?->format('H:i') }}
                                </td>
                                <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">
                                    {{ $session->booked_count }} / {{ $session->capacity }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-bold text-[#247451]">
                                    {{ $session->remaining() }} slots
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-[#61727D]">
                                    No collection sessions scheduled yet. Click "+ Schedule Collection Session" above to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sessions->hasPages())
                <div class="p-4 border-t border-[#D5DEDF]">
                    {{ $sessions->links() }}
                </div>
            @endif
        </div>

        <!-- Schedule Session Modal -->
        <div x-show="sessionModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="sessionModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-lg w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Schedule Phlebotomy Session</h3>
                    <button @click="sessionModalOpen = false" class="text-gray-400 hover:text-black font-bold">&times;</button>
                </div>

                <form action="{{ route('corporate.sessions.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Select Screening Program <span class="text-red-500">*</span></label>
                        <select name="screening_program_id" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                            @foreach ($programs as $prog)
                                <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Station Location / Venue <span class="text-red-500">*</span></label>
                        <input type="text" name="location" placeholder="e.g. Accra HQ Medical Suite / Takoradi Base Clinic" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Collection Mode <span class="text-red-500">*</span></label>
                            <select name="mode" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                                <option value="mobile_onsite">Mobile On-Site Phlebotomy Camp</option>
                                <option value="partner_walk_in">Partner Laboratory Walk-In</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Session Capacity (Staff) <span class="text-red-500">*</span></label>
                            <input type="number" name="capacity" value="50" min="5" max="500" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Starts At <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="starts_at" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Ends At <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="ends_at" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="sessionModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                            Confirm Session
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.workspace>
