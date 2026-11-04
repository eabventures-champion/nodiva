<x-layouts.workspace title="Corporate Health Screening — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-corporate-sidebar active="dashboard" />
    </x-slot:sidebar>

    <div x-data="{ programModalOpen: false }">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#102A36]">Corporate Health Screening Console</h1>
                <p class="text-xs text-[#61727D]">Workplace wellness, pre-employment checks, and occupational health programs across Ghana.</p>
            </div>
            <button @click="programModalOpen = true" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition shadow-sm">
                + Create Screening Program
            </button>
        </div>


        <!-- Stat Metric Boxes -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#102A36] uppercase tracking-wider">Active Screening Programs</div>
                <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $counts['active_programs'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Enrolled Workforce</div>
                <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $counts['total_participants'] }}</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Completed Screenings</div>
                <div class="text-2xl font-bold text-[#247451] mt-1">{{ $counts['completed_screenings'] }}</div>
            </div>
        </div>

        <!-- Corporate Privacy Safeguard Notice -->
        <div class="p-4 bg-[#E8F0EF] border border-[#167C78]/30 flex items-start space-x-3 text-xs text-[#172A35]">
            <svg class="w-5 h-5 text-[#167C78] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <div>
                <span class="font-bold block text-[#102A36]">Employee Confidentiality Safeguard (Data Protection Act 2012)</span>
                Employer portal access displays operational participation and aggregate statistics only. Individual medical values are never exposed to employers without employee statutory consent.
            </div>
        </div>

        <!-- Active Programs Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Screening Programs</h2>
                <span class="text-xs text-[#61727D]">{{ $programs->count() }} active program(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Program Name</th>
                            <th class="px-6 py-3">Package / Scope</th>
                            <th class="px-6 py-3">Partner Laboratory</th>
                            <th class="px-6 py-3">Schedule Period</th>
                            <th class="px-6 py-3">Enrolled</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($programs as $prog)
                            <tr class="hover:bg-[#E8F0EF]/40 transition">
                                <td class="px-6 py-3.5 font-bold text-[#102A36]">
                                    {{ $prog->name }}
                                </td>
                                <td class="px-6 py-3.5">{{ $prog->package?->name ?? 'Custom Scope' }}</td>
                                <td class="px-6 py-3.5">{{ $prog->laboratory?->name ?? 'Network Laboratory' }}</td>
                                <td class="px-6 py-3.5 text-[#61727D]">
                                    {{ $prog->starts_on ? $prog->starts_on->format('M d') : 'Now' }} - {{ $prog->ends_on ? $prog->ends_on->format('M d, Y') : 'Ongoing' }}
                                </td>
                                <td class="px-6 py-3.5 font-bold text-[#167C78]">
                                    {{ $prog->participants->count() }} staff
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-emerald-100 text-emerald-800">
                                        {{ $prog->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right font-medium">
                                    <a href="{{ route('corporate.sessions.index') }}" class="text-[#167C78] hover:underline font-bold">
                                        Sessions &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-[#61727D]">
                                    No screening programs configured yet. Click "+ Create Screening Program" above to launch one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Screening Program Modal -->
        <div x-show="programModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="programModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-lg w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Schedule Corporate Screening Program</h3>
                    <button @click="programModalOpen = false" class="text-gray-400 hover:text-black font-bold">&times;</button>
                </div>

                <form action="{{ route('corporate.programs.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Program Title <span class="text-red-500">*</span></label>
                        <input type="text" name="name" placeholder="e.g. 2026 Executive & Workforce Annual Medicals" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Diagnostic Screening Package <span class="text-red-500">*</span></label>
                        <select name="screening_package_id" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                            <option value="">-- Choose Standard Package --</option>
                            @foreach ($packages as $pkg)
                                <option value="{{ $pkg->id }}">{{ $pkg->name }} ({{ count($pkg->tests) }} investigations)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Partner Testing Laboratory <span class="text-red-500">*</span></label>
                        <select name="laboratory_id" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                            <option value="">-- Select Accredited Partner Lab --</option>
                            @foreach ($laboratories as $lab)
                                <option value="{{ $lab->id }}">{{ $lab->name }} ({{ $lab->city }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Program Medical Purpose <span class="text-red-500">*</span></label>
                        <select name="purpose" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                            <option value="annual_medical">Annual Corporate Health Checkup</option>
                            <option value="pre_employment">Pre-Employment Medical Fitness Check</option>
                            <option value="occupational_health">Occupational Health & Heavy Industry Surveillance</option>
                            <option value="risk_screening">Targeted Cardio-Metabolic Risk Screening</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Starts On <span class="text-red-500">*</span></label>
                            <input type="date" name="starts_on" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Ends On <span class="text-red-500">*</span></label>
                            <input type="date" name="ends_on" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="programModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                            Activate Program
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.workspace>
