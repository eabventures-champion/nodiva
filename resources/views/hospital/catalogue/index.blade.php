<x-layouts.workspace title="Diagnostic Test Catalogue — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-hospital-sidebar active="catalogue" />
    </x-slot:sidebar>

    <!-- Header & Search -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#102A36]">Diagnostic Test Catalogue</h1>
            <p class="text-xs text-[#61727D]">Accredited laboratory diagnostic menu, container transport specifications, and pre-analytical patient preparation.</p>
        </div>
        <form method="GET" action="{{ route('hospital.catalogue.index') }}" class="w-full md:w-80">
            <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Search test name, code, or LOINC..."
                   class="w-full text-xs border border-[#D5DEDF] px-3.5 py-2 focus:border-[#167C78] focus:outline-none bg-white">
        </form>
    </div>

    <!-- Specialty Filters -->
    <div class="flex gap-2 overflow-x-auto pb-1 text-xs font-semibold">
        <a href="{{ route('hospital.catalogue.index', array_filter(['q' => $searchQuery])) }}"
           class="px-3 py-1.5 border whitespace-nowrap {{ empty($currentSpecialty) ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
            All Specialties
        </a>
        @foreach ($specialties as $spec)
            <a href="{{ route('hospital.catalogue.index', array_filter(['q' => $searchQuery, 'specialty' => $spec->id])) }}"
               class="px-3 py-1.5 border whitespace-nowrap {{ (string)$currentSpecialty === (string)$spec->id ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#F5F3ED]' }}">
                {{ $spec->name }}
            </a>
        @endforeach
    </div>

    <!-- Test Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($tests as $test)
            <div class="bg-white border border-[#D5DEDF] p-5 flex flex-col justify-between hover:border-[#167C78] transition space-y-4">
                <div class="space-y-2">
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-[10px] font-mono uppercase bg-[#F5F3ED] text-[#61727D] px-2 py-0.5 border border-[#D5DEDF]">
                            {{ $test->code }}
                        </span>
                        <span class="text-[10px] text-[#167C78] font-bold uppercase tracking-wider">
                            {{ $test->specialty?->name }}
                        </span>
                    </div>

                    <h3 class="font-bold text-sm text-[#102A36] leading-tight">{{ $test->name }}</h3>
                    
                    @if ($test->description)
                        <p class="text-xs text-[#61727D] line-clamp-2 leading-relaxed">
                            {{ $test->description }}
                        </p>
                    @endif

                    <!-- Pre-Analytical Specimen Requirements -->
                    <div class="bg-[#F5F3ED]/60 p-2.5 border border-[#D5DEDF] space-y-1.5 text-[11px]">
                        <div class="flex justify-between text-[#61727D]">
                            <span>Specimen Container:</span>
                            <strong class="text-[#102A36]">{{ $test->specimenType?->container ?? 'Standard Tube' }}</strong>
                        </div>
                        <div class="flex justify-between text-[#61727D]">
                            <span>Transport Condition:</span>
                            <span class="font-bold text-[#167C78] capitalize">{{ $test->specimenType?->transport_requirements ?? 'ambient' }}</span>
                        </div>
                        @if ($test->patient_preparation)
                            <div class="text-[10px] text-[#94611A] pt-1 border-t border-[#D5DEDF]">
                                <strong>Prep:</strong> {{ $test->patient_preparation }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="pt-3 border-t border-[#D5DEDF] flex items-center justify-between">
                    <div>
                        <div class="text-[10px] text-[#61727D] uppercase font-bold">Standard TAT</div>
                        <div class="text-xs font-bold text-[#102A36]">
                            {{ $test->catalogueEntries->isNotEmpty() ? $test->catalogueEntries->min('turnaround_hours') . ' hours' : '12-24 hours' }}
                        </div>
                    </div>
                    <a href="{{ route('hospital.orders.create', ['test_id' => $test->id]) }}" 
                       class="px-3 py-1.5 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition">
                        Order Test &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 bg-white border border-[#D5DEDF] p-12 text-center text-[#61727D]">
                No diagnostic tests found matching your criteria.
            </div>
        @endforelse
    </div>

    @if ($tests->hasPages())
        <div class="p-4 bg-white border border-[#D5DEDF]">
            {{ $tests->links() }}
        </div>
    @endif
</x-layouts.workspace>
