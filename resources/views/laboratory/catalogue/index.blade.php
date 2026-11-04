<x-layouts.workspace title="Test Catalogue & Capacity — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-lab-sidebar active="catalogue" />
    </x-slot:sidebar>

    <div x-data="{ addTestModalOpen: false, editModalOpen: false, currentEntry: null }">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#102A36]">Laboratory Diagnostic Menu & Capacity</h1>
                <p class="text-xs text-[#61727D]">Configure your laboratory's active diagnostic investigations, turnaround times, and daily analyzer throughput.</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($availableTests->isNotEmpty())
                    <form action="{{ route('lab.catalogue.initialize') }}" method="POST" onsubmit="return confirm('Quick Setup will import available Master Formulary tests with default contract pricing (GH₵ 250) and 24h TAT benchmark. Proceed?');">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 border border-[#167C78] text-[#167C78] hover:bg-[#167C78] hover:text-white text-xs font-bold uppercase tracking-wider transition">
                            ⚡ Quick Setup (Master Tests)
                        </button>
                    </form>
                    <button @click="addTestModalOpen = true" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition shadow-sm">
                        + Add Investigation
                    </button>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                {{ session('success') }}
            </div>
        @endif

        <!-- Active Catalogue Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Active Test Catalogue Entries</h2>
                <span class="text-xs text-[#61727D]">{{ $entries->total() }} active investigation(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Internal Code</th>
                            <th class="px-6 py-3">Diagnostic Test Name</th>
                            <th class="px-6 py-3">Specialty</th>
                            <th class="px-6 py-3">Specimen Tube</th>
                            <th class="px-6 py-3">Contract Price</th>
                            <th class="px-6 py-3">TAT Target</th>
                            <th class="px-6 py-3">Daily Capacity</th>
                            <th class="px-6 py-3">Availability</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($entries as $entry)
                            <tr class="hover:bg-[#E8F0EF]/40 transition">
                                <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">{{ $entry->lab_test_code ?: ($entry->test->code ?? 'N/A') }}</td>
                                <td class="px-6 py-3.5">
                                    <div class="font-bold text-[#102A36]">{{ $entry->test->name }}</div>
                                    <div class="text-[10px] font-mono text-[#61727D]">{{ $entry->test->code }}</div>
                                </td>
                                <td class="px-6 py-3.5">{{ $entry->test->specialty?->name }}</td>
                                <td class="px-6 py-3.5">{{ $entry->test->specimenType?->container }}</td>
                                <td class="px-6 py-3.5 font-bold font-mono text-[#102A36]">GH₵ {{ number_format((float)$entry->price, 2) }}</td>
                                <td class="px-6 py-3.5 font-bold text-[#167C78]">{{ $entry->turnaround_hours }} hrs</td>
                                <td class="px-6 py-3.5">{{ $entry->daily_capacity }} / day</td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm {{ $entry->is_available ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $entry->is_available ? 'Online' : 'Restricted' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right font-medium">
                                    <div class="inline-flex items-center gap-3">
                                        <button type="button" @click="currentEntry = {{ json_encode($entry) }}; editModalOpen = true"
                                                class="text-[#167C78] hover:underline font-bold">
                                            Edit &rarr;
                                        </button>
                                        <form action="{{ route('lab.catalogue.destroy', $entry) }}" method="POST"
                                              onsubmit="return confirm('Remove \'{{ addslashes($entry->test->name) }}\' from your active diagnostic menu? Hospitals will no longer be able to route this test to your laboratory.');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-800 hover:underline font-semibold">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center">
                                    <div class="max-w-md mx-auto space-y-4">
                                        <div class="w-12 h-12 mx-auto rounded-full bg-amber-50 text-[#94611A] flex items-center justify-center font-bold text-xl border border-amber-200">
                                            !
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Diagnostic Menu is Currently Empty</h3>
                                            <p class="text-xs text-[#61727D] mt-1 leading-relaxed">
                                                Your laboratory has no diagnostic tests configured. Referring hospitals and the automated routing engine cannot dispatch referral requests to your facility until you populate your supported investigations and contract prices.
                                            </p>
                                        </div>
                                        <div class="flex items-center justify-center gap-3 pt-2">
                                            @if ($availableTests->isNotEmpty())
                                                <form action="{{ route('lab.catalogue.initialize') }}" method="POST" onsubmit="return confirm('Initialize your laboratory menu with all available Master Formulary tests? You can edit codes and pricing at any time.');">
                                                    @csrf
                                                    <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition shadow-sm">
                                                        ⚡ Quick Setup from Master Formulary
                                                    </button>
                                                </form>
                                                <button @click="addTestModalOpen = true" class="px-4 py-2 border border-[#D5DEDF] text-[#102A36] hover:bg-[#F5F3ED] text-xs font-bold uppercase tracking-wider transition">
                                                    + Add Individual Test
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($entries->hasPages())
                <div class="p-4 border-t border-[#D5DEDF]">
                    {{ $entries->links() }}
                </div>
            @endif
        </div>

        <!-- Add Test Modal -->
        <div x-show="addTestModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="addTestModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-lg w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Add Test to Laboratory Menu</h3>
                    <button @click="addTestModalOpen = false" class="text-gray-400 hover:text-black font-bold">&times;</button>
                </div>

                <form action="{{ route('lab.catalogue.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Select Diagnostic Investigation <span class="text-red-500">*</span></label>
                        <select name="diagnostic_test_id" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                            <option value="">-- Choose Master Investigation --</option>
                            @foreach ($availableTests as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }} • {{ $t->specialty?->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Internal Lab Code <span class="text-red-500">*</span></label>
                            <input type="text" name="lab_test_code" placeholder="e.g. SL-MOL-015" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Contract Price (GHS) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="price" placeholder="450.00" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Turnaround Time (Hours) <span class="text-red-500">*</span></label>
                            <input type="number" name="turnaround_hours" value="24" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Daily Capacity Limit</label>
                            <input type="number" name="daily_capacity" value="100" class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="addTestModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                            Save to Menu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Test Modal -->
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="editModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Update Test Pricing & Capacity</h3>
                    <button @click="editModalOpen = false" class="text-gray-400 hover:text-black font-bold">&times;</button>
                </div>

                <form :action="'/lab/catalogue/' + (currentEntry ? currentEntry.id : '')" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PATCH')
                    <div>
                        <div class="text-[#61727D] mb-1">Test: <strong class="text-[#102A36]" x-text="currentEntry?.test?.name"></strong></div>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Internal Laboratory Code <span class="text-red-500">*</span></label>
                        <input type="text" name="lab_test_code" :value="currentEntry?.lab_test_code" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none font-mono font-bold">
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Contract Price (GHS) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="price" :value="currentEntry?.price" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none font-mono">
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Turnaround Target (Hours) <span class="text-red-500">*</span></label>
                        <input type="number" name="turnaround_hours" :value="currentEntry?.turnaround_hours" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Daily Capacity</label>
                        <input type="number" name="daily_capacity" :value="currentEntry?.daily_capacity" class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_available" value="1" :checked="currentEntry?.is_available" class="text-[#167C78] focus:ring-[#167C78]">
                            <span class="font-bold text-[#102A36]">Active & Available for Matching Engine</span>
                        </label>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.workspace>
