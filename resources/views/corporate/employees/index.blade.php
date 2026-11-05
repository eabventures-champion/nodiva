<x-layouts.workspace title="Employee Rosters — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-corporate-sidebar active="employees" />
    </x-slot:sidebar>

    <div x-data="{ addModalOpen: false }">
        <!-- Header & Search -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#102A36]">Corporate Employee Rosters</h1>
                <p class="text-xs text-[#61727D]">Manage organizational workforce rosters, demographic profiles, and diagnostic screening enrollment.</p>
            </div>
            <div class="flex items-center gap-3 w-full md:w-auto">
                <button @click="addModalOpen = true" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition shadow-sm whitespace-nowrap">
                    + Enroll Employee
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filter & Search Bar -->
        <div class="flex flex-col sm:flex-row gap-3 text-xs">
            <form method="GET" action="{{ route('corporate.employees.index') }}" class="flex-1 flex gap-2">
                <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Search by staff ID or department..."
                       class="w-full text-xs border border-[#D5DEDF] px-3.5 py-2 focus:border-[#167C78] focus:outline-none bg-white">
                <button type="submit" class="px-4 py-2 bg-[#102A36] text-white font-bold uppercase tracking-wider">
                    Search
                </button>
            </form>

            @if ($departments->isNotEmpty())
                <div class="flex gap-1 overflow-x-auto pb-1">
                    <a href="{{ route('corporate.employees.index') }}" 
                       class="px-3 py-1.5 border whitespace-nowrap {{ empty($currentDept) ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF]' }}">
                        All Departments
                    </a>
                    @foreach ($departments as $d)
                        <a href="{{ route('corporate.employees.index', ['department' => $d]) }}" 
                           class="px-3 py-1.5 border whitespace-nowrap {{ $currentDept === $d ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF]' }}">
                            {{ $d }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Employees Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Active Staff Members</h2>
                <span class="text-xs text-[#61727D]">{{ $employees->total() }} total employee(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Staff ID Number</th>
                            <th class="px-6 py-3">Employee Name</th>
                            <th class="px-6 py-3">Department</th>
                            <th class="px-6 py-3">Demographics</th>
                            <th class="px-6 py-3">Contact</th>
                            <th class="px-6 py-3 text-right">Program Enrollment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($employees as $emp)
                            <tr class="hover:bg-[#E8F0EF]/40 transition">
                                <td class="px-6 py-3.5 font-mono font-bold text-[#102A36]">{{ $emp->employee_number }}</td>
                                <td class="px-6 py-3.5 font-bold text-[#102A36]">{{ $emp->full_name }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 text-[10px] bg-[#E8F0EF] text-[#167C78] font-bold rounded-sm">
                                        {{ $emp->department ?? 'General' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 capitalize text-[#61727D]">{{ $emp->sex }} • Born {{ $emp->birth_year }}</td>
                                <td class="px-6 py-3.5 text-[#61727D]">{{ $emp->email }}</td>
                                <td class="px-6 py-3.5 text-right font-medium">
                                    @if ($emp->participations->isNotEmpty())
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-emerald-100 text-emerald-800">
                                            {{ $emp->participations->first()->status }}
                                        </span>
                                    @else
                                        <span class="text-[#61727D] text-[11px]">Eligible</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-[#61727D]">
                                    No employee records found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($employees->hasPages())
                <div class="p-4 border-t border-[#D5DEDF]">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>

        <!-- Add Employee Modal -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="addModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-lg w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Enroll Employee into Roster</h3>
                    <button @click="addModalOpen = false" class="text-gray-400 hover:text-black font-bold">&times;</button>
                </div>

                <form action="{{ route('corporate.employees.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Staff ID Number <span class="text-red-500">*</span></label>
                            <input type="text" name="employee_number" placeholder="e.g. TUL-GH-0240" required class="w-full border border-[#D5DEDF] p-2 font-mono focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Full Legal Name <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" placeholder="First & Last Name" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Corporate Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" placeholder="staff@tullow.example" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Department / Division <span class="text-red-500">*</span></label>
                            <input type="text" name="department" placeholder="e.g. Subsea Operations" required class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Biological Sex <span class="text-red-500">*</span></label>
                            <select name="sex" required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Birth Year</label>
                            <input type="number" name="birth_year" value="1990" class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="addModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                            Enroll Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.workspace>
