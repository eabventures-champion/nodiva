<x-layouts.workspace title="Master Diagnostic Test Catalogue — NODIVA Operations" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="catalogue" />
    </x-slot:sidebar>

    <div x-data="{
        createModalOpen: false,
        editModalOpen: false,
        manageModalOpen: false,
        manageTab: 'specialties',
        currentTest: null,
        specialtiesList: {{ Js::from($specialties) }},
        specimenTypesList: {{ Js::from($specimenTypes) }},
        createSpecialtyId: '',
        editSpecialtyId: '',
        createSpecimenTypeId: '',
        editSpecimenTypeId: '',
        showNewSpecialtyCreate: false,
        showNewSpecialtyEdit: false,
        showNewSpecimenTypeCreate: false,
        showNewSpecimenTypeEdit: false,
        newSpecialtyName: '',
        newSpecialtyCode: '',
        newSpecimenContainer: '',
        newSpecimenName: '',
        newSpecimenCode: '',
        newSpecimenTransport: 'ambient',
        newSpecimenStability: '',
        savingSpecialty: false,
        savingSpecimenType: false,
        specialtyError: '',
        specimenTypeError: '',

        // Inline management states
        editingSpecialtyId: null,
        editSpecialtyNameInput: '',
        editSpecialtyCodeInput: '',
        editingSpecimenId: null,
        editSpecimenContainerInput: '',
        editSpecimenNameInput: '',
        editSpecimenCodeInput: '',
        editSpecimenTransportInput: 'ambient',
        editSpecimenStabilityInput: '',
        manageActionLoading: false,
        manageActionError: '',
        manageActionSuccess: '',

        openEdit(test) {
            this.currentTest = test;
            this.editSpecialtyId = test.diagnostic_specialty_id;
            this.editSpecimenTypeId = test.specimen_type_id;
            this.showNewSpecialtyEdit = false;
            this.showNewSpecimenTypeEdit = false;
            this.specialtyError = '';
            this.specimenTypeError = '';
            this.editModalOpen = true;
        },

        async addNewSpecialty(context = 'create') {
            if (!this.newSpecialtyName.trim()) return;
            this.savingSpecialty = true;
            this.specialtyError = '';
            try {
                const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                const response = await fetch('{{ route('ops.specialties.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        name: this.newSpecialtyName.trim(),
                        code: this.newSpecialtyCode.trim() || null
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Failed to add specialty.'));
                }

                if (!this.specialtiesList.some(s => s.id === data.specialty.id)) {
                    data.specialty.tests_count = 0;
                    this.specialtiesList.push(data.specialty);
                    this.specialtiesList.sort((a, b) => a.name.localeCompare(b.name));
                }

                if (context === 'create') {
                    this.createSpecialtyId = data.specialty.id;
                    this.showNewSpecialtyCreate = false;
                } else {
                    this.editSpecialtyId = data.specialty.id;
                    this.showNewSpecialtyEdit = false;
                }

                this.newSpecialtyName = '';
                this.newSpecialtyCode = '';
            } catch (err) {
                this.specialtyError = err.message;
            } finally {
                this.savingSpecialty = false;
            }
        },

        async addNewSpecimenType(context = 'create') {
            if (!this.newSpecimenContainer.trim()) return;
            this.savingSpecimenType = true;
            this.specimenTypeError = '';
            try {
                const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                const response = await fetch('{{ route('ops.specimen-types.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        container: this.newSpecimenContainer.trim(),
                        name: this.newSpecimenName.trim() || null,
                        code: this.newSpecimenCode.trim() || null,
                        transport_requirements: this.newSpecimenTransport,
                        stability_hours: this.newSpecimenStability ? parseInt(this.newSpecimenStability) : null
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Failed to add specimen container.'));
                }

                if (!this.specimenTypesList.some(s => s.id === data.specimenType.id)) {
                    data.specimenType.tests_count = 0;
                    this.specimenTypesList.push(data.specimenType);
                    this.specimenTypesList.sort((a, b) => (a.container || '').localeCompare(b.container || ''));
                }

                if (context === 'create') {
                    this.createSpecimenTypeId = data.specimenType.id;
                    this.showNewSpecimenTypeCreate = false;
                } else {
                    this.editSpecimenTypeId = data.specimenType.id;
                    this.showNewSpecimenTypeEdit = false;
                }

                this.newSpecimenContainer = '';
                this.newSpecimenName = '';
                this.newSpecimenCode = '';
                this.newSpecimenTransport = 'ambient';
                this.newSpecimenStability = '';
            } catch (err) {
                this.specimenTypeError = err.message;
            } finally {
                this.savingSpecimenType = false;
            }
        },

        // Management Actions: Specialties
        startEditSpecialty(spec) {
            this.editingSpecialtyId = spec.id;
            this.editSpecialtyNameInput = spec.name;
            this.editSpecialtyCodeInput = spec.code;
            this.manageActionError = '';
            this.manageActionSuccess = '';
        },

        cancelEditSpecialty() {
            this.editingSpecialtyId = null;
            this.manageActionError = '';
        },

        async saveEditSpecialty(spec) {
            if (!this.editSpecialtyNameInput.trim()) return;
            this.manageActionLoading = true;
            this.manageActionError = '';
            this.manageActionSuccess = '';
            try {
                const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                const response = await fetch(`/ops/specialties/${spec.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        name: this.editSpecialtyNameInput.trim(),
                        code: this.editSpecialtyCodeInput.trim() || null
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Update failed.'));
                }

                spec.name = data.specialty.name;
                spec.code = data.specialty.code;
                this.editingSpecialtyId = null;
                this.manageActionSuccess = data.message;
            } catch (err) {
                this.manageActionError = err.message;
            } finally {
                this.manageActionLoading = false;
            }
        },

        async deleteSpecialty(spec) {
            if (!confirm(`Are you sure you want to remove the specialty '${spec.name}'?`)) return;
            this.manageActionLoading = true;
            this.manageActionError = '';
            this.manageActionSuccess = '';
            try {
                const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                const response = await fetch(`/ops/specialties/${spec.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Deletion failed.');
                }

                this.specialtiesList = this.specialtiesList.filter(s => s.id !== spec.id);
                if (this.createSpecialtyId == spec.id) this.createSpecialtyId = '';
                if (this.editSpecialtyId == spec.id) this.editSpecialtyId = '';
                this.manageActionSuccess = data.message;
            } catch (err) {
                this.manageActionError = err.message;
            } finally {
                this.manageActionLoading = false;
            }
        },

        // Management Actions: Specimen Types
        startEditSpecimenType(st) {
            this.editingSpecimenId = st.id;
            this.editSpecimenContainerInput = st.container;
            this.editSpecimenNameInput = st.name || '';
            this.editSpecimenCodeInput = st.code || '';
            this.editSpecimenTransportInput = st.transport_requirements || 'ambient';
            this.editSpecimenStabilityInput = st.stability_hours || '';
            this.manageActionError = '';
            this.manageActionSuccess = '';
        },

        cancelEditSpecimenType() {
            this.editingSpecimenId = null;
            this.manageActionError = '';
        },

        async saveEditSpecimenType(st) {
            if (!this.editSpecimenContainerInput.trim()) return;
            this.manageActionLoading = true;
            this.manageActionError = '';
            this.manageActionSuccess = '';
            try {
                const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                const response = await fetch(`/ops/specimen-types/${st.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        container: this.editSpecimenContainerInput.trim(),
                        name: this.editSpecimenNameInput.trim() || null,
                        code: this.editSpecimenCodeInput.trim() || null,
                        transport_requirements: this.editSpecimenTransportInput,
                        stability_hours: this.editSpecimenStabilityInput ? parseInt(this.editSpecimenStabilityInput) : null
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Update failed.'));
                }

                st.container = data.specimenType.container;
                st.name = data.specimenType.name;
                st.code = data.specimenType.code;
                st.transport_requirements = data.specimenType.transport_requirements;
                st.stability_hours = data.specimenType.stability_hours;
                this.editingSpecimenId = null;
                this.manageActionSuccess = data.message;
            } catch (err) {
                this.manageActionError = err.message;
            } finally {
                this.manageActionLoading = false;
            }
        },

        async deleteSpecimenType(st) {
            if (!confirm(`Are you sure you want to remove the specimen tube '${st.container}'?`)) return;
            this.manageActionLoading = true;
            this.manageActionError = '';
            this.manageActionSuccess = '';
            try {
                const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                const response = await fetch(`/ops/specimen-types/${st.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Deletion failed.');
                }

                this.specimenTypesList = this.specimenTypesList.filter(s => s.id !== st.id);
                if (this.createSpecimenTypeId == st.id) this.createSpecimenTypeId = '';
                if (this.editSpecimenTypeId == st.id) this.editSpecimenTypeId = '';
                this.manageActionSuccess = data.message;
            } catch (err) {
                this.manageActionError = err.message;
            } finally {
                this.manageActionLoading = false;
            }
        }
    }">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#102A36]">Master Diagnostic Test Catalogue</h1>
                <p class="text-xs text-[#61727D]">National diagnostic formulary, standardized LOINC interoperability codes, specimen container specs, and pre-analytical instructions.</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="manageModalOpen = true; manageTab = 'specialties'; manageActionError = ''; manageActionSuccess = ''"
                        class="px-3.5 py-2 border border-[#D5DEDF] bg-white hover:bg-[#F5F3ED] text-[#102A36] text-xs font-bold uppercase tracking-wider transition shadow-2xs flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Manage Specialties & Tubes</span>
                </button>
                <button @click="createModalOpen = true; showNewSpecialtyCreate = false; showNewSpecimenTypeCreate = false; specialtyError = ''; specimenTypeError = ''" 
                        class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition shadow-sm flex items-center gap-1.5">
                    <span>+</span>
                    <span>Create Diagnostic Test</span>
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-red-50 border border-red-200 text-red-800 text-xs space-y-1">
                <div class="font-bold">Please correct the following errors:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter & Search Bar -->
        <div class="bg-white border border-[#D5DEDF] p-4 flex flex-col md:flex-row gap-4 justify-between items-stretch md:items-center">
            <form method="GET" action="{{ route('ops.catalogue.index') }}" class="flex-1 flex flex-col sm:flex-row gap-3">
                <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Search by test name, code, or LOINC..."
                       class="text-xs border border-[#D5DEDF] px-3.5 py-2 focus:border-[#167C78] focus:outline-none bg-white flex-1">

                <select name="specialty" class="text-xs border border-[#D5DEDF] px-3 py-2 bg-white focus:border-[#167C78] focus:outline-none">
                    <option value="">All Specialties</option>
                    <template x-for="spec in specialtiesList" :key="spec.id">
                        <option :value="spec.id" :selected="'{{ $currentSpecialty }}' == spec.id" x-text="spec.name"></option>
                    </template>
                </select>

                <select name="status" class="text-xs border border-[#D5DEDF] px-3 py-2 bg-white focus:border-[#167C78] focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-[#102A36] text-white text-xs font-bold hover:bg-[#193B49] transition">
                    Filter
                </button>

                @if ($searchQuery || $currentSpecialty || $currentStatus)
                    <a href="{{ route('ops.catalogue.index') }}" class="px-3 py-2 border border-[#D5DEDF] text-[#61727D] hover:bg-[#F5F3ED] text-xs font-bold flex items-center justify-center">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Catalogue Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center bg-[#F5F3ED]/30">
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Master Formulations Directory</h2>
                <span class="text-xs text-[#61727D] font-mono">{{ $tests->total() }} diagnostic investigation(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Code & LOINC</th>
                            <th class="px-6 py-3">Diagnostic Test Name</th>
                            <th class="px-6 py-3">Specialty</th>
                            <th class="px-6 py-3">Specimen Tube / Transport</th>
                            <th class="px-6 py-3">Preparation</th>
                            <th class="px-6 py-3 text-center">Fulfilling Labs</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($tests as $t)
                            <tr class="hover:bg-[#E8F0EF]/30 transition">
                                <td class="px-6 py-3.5">
                                    <div class="font-mono font-bold text-[#102A36]">{{ $t->code }}</div>
                                    <div class="font-mono text-[10px] text-[#61727D]">LOINC: {{ $t->loinc_code ?? 'None' }}</div>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="font-bold text-[#102A36] text-[13px]">{{ $t->name }}</div>
                                    @if ($t->description)
                                        <div class="text-[11px] text-[#61727D] line-clamp-1 mt-0.5">{{ $t->description }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-[#102A36]">
                                    <span class="px-2 py-0.5 text-[10px] bg-[#E8F0EF] text-[#167C78] font-bold rounded-sm">
                                        {{ $t->specialty?->name }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="font-semibold text-[#102A36]">{{ $t->specimenType?->container ?? 'Standard Tube' }}</div>
                                    <div class="text-[10px] text-[#167C78] capitalize font-medium">{{ $t->specimenType?->transport_requirements ?? 'ambient' }}</div>
                                </td>
                                <td class="px-6 py-3.5 text-[#61727D] text-[11px]">
                                    {{ $t->patient_preparation ? \Illuminate\Support\Str::limit($t->patient_preparation, 30) : 'None specified' }}
                                </td>
                                <td class="px-6 py-3.5 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="font-bold font-mono text-xs {{ $t->catalogue_entries_count > 0 ? 'text-[#167C78]' : 'text-gray-400' }}">
                                            {{ $t->catalogue_entries_count }} lab(s)
                                        </span>
                                        @if ($t->catalogueEntries->isNotEmpty())
                                            <div class="flex flex-wrap justify-center gap-1 max-w-[220px]">
                                                @foreach ($t->catalogueEntries as $entry)
                                                    <span class="px-1.5 py-0.5 text-[9px] font-semibold {{ $entry->is_available ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-gray-100 text-gray-500 border-gray-200' }} border rounded-sm"
                                                          title="{{ $entry->laboratory?->name }} • {{ $entry->is_available ? 'Active/Online' : 'Offline' }} • GH₵ {{ number_format((float)$entry->price, 2) }} • {{ $entry->turnaround_hours }}h TAT">
                                                        {{ $entry->laboratory?->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-[10px] text-gray-400 italic">No fulfilling labs</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 text-center">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm {{ $t->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $t->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right font-medium">
                                    <button type="button" @click="openEdit({{ json_encode($t) }})"
                                            class="text-[#167C78] hover:underline font-bold">
                                        Edit &rarr;
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-[#61727D]">
                                    No master diagnostic tests found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tests->hasPages())
                <div class="p-4 border-t border-[#D5DEDF]">
                    {{ $tests->links() }}
                </div>
            @endif
        </div>

        <!-- Create Test Modal -->
        <div x-show="createModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="createModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-2xl w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Create Master Diagnostic Test</h3>
                    <button @click="createModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form action="{{ route('ops.catalogue.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Standard Test Code <span class="text-red-500">*</span></label>
                            <input type="text" name="code" placeholder="e.g. CRP_QUANT, TROPONIN_I" required
                                   class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none font-mono uppercase">
                            <span class="text-[10px] text-[#61727D]">Uppercase letters, numbers and underscores only.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">LOINC Standard Code</label>
                            <input type="text" name="loinc_code" placeholder="e.g. 1988-5, 49563-0"
                                   class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none font-mono">
                            <span class="text-[10px] text-[#61727D]">Logical Observation Identifiers Names and Codes.</span>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Full Diagnostic Test Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" placeholder="e.g. C-Reactive Protein (CRP) High Sensitivity Quantitative" required
                               class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Diagnostic Specialty Field with Inline Creation & Manage Actions -->
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block font-bold text-[#102A36]">Diagnostic Specialty <span class="text-red-500">*</span></label>
                                <div class="flex items-center gap-1.5 text-[11px]">
                                    <button type="button" @click="showNewSpecialtyCreate = !showNewSpecialtyCreate; specialtyError = ''" 
                                            class="text-[#167C78] hover:text-[#136663] font-bold transition">
                                        <span x-show="!showNewSpecialtyCreate">+ Add New</span>
                                        <span x-show="showNewSpecialtyCreate" class="text-red-600">Cancel</span>
                                    </button>
                                    <span class="text-[#D5DEDF]">•</span>
                                    <button type="button" @click="manageModalOpen = true; manageTab = 'specialties'"
                                            class="text-[#61727D] hover:text-[#102A36] font-semibold underline">
                                        Manage
                                    </button>
                                </div>
                            </div>

                            <!-- Inline Add Specialty Form -->
                            <div x-show="showNewSpecialtyCreate" x-cloak class="mb-2 p-2.5 bg-[#E8F0EF] border border-[#167C78] space-y-2 rounded-xs">
                                <div class="text-[11px] font-bold text-[#102A36] uppercase tracking-wider flex items-center justify-between">
                                    <span>New Specialty</span>
                                    <span class="text-[10px] text-[#61727D] font-normal">Saves instantly to network</span>
                                </div>
                                <div class="space-y-1.5">
                                    <input type="text" x-model="newSpecialtyName" @keydown.enter.prevent="addNewSpecialty('create')"
                                           placeholder="Specialty Name (e.g. Immunology & Serology)"
                                           class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                    <div class="flex items-center gap-2">
                                        <input type="text" x-model="newSpecialtyCode" @keydown.enter.prevent="addNewSpecialty('create')"
                                               placeholder="Code (optional, e.g. IMMUNO)"
                                               class="w-1/2 border border-[#D5DEDF] p-1.5 text-xs bg-white font-mono uppercase focus:border-[#167C78] focus:outline-none">
                                        <button type="button" @click="addNewSpecialty('create')" :disabled="savingSpecialty || !newSpecialtyName.trim()"
                                                class="w-1/2 py-1.5 px-3 bg-[#167C78] hover:bg-[#136663] disabled:opacity-50 text-white font-bold text-[10px] uppercase tracking-wider transition">
                                            <span x-text="savingSpecialty ? 'Adding...' : 'Add & Select'"></span>
                                        </button>
                                    </div>
                                    <div x-show="specialtyError" x-text="specialtyError" class="text-[10px] text-red-600 font-bold"></div>
                                </div>
                            </div>

                            <select name="diagnostic_specialty_id" x-model="createSpecialtyId" 
                                    @change="if($event.target.value === '__add_new__') { showNewSpecialtyCreate = true; createSpecialtyId = ''; }"
                                    required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                                <option value="">-- Select Specialty --</option>
                                <template x-for="spec in specialtiesList" :key="spec.id">
                                    <option :value="spec.id" x-text="spec.name"></option>
                                </template>
                                <option value="__add_new__" class="font-bold text-[#167C78] bg-[#E8F0EF]/40">+ Add New Specialty...</option>
                            </select>
                        </div>

                        <!-- Specimen Tube / Requirements Field with Inline Creation & Manage Actions -->
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block font-bold text-[#102A36]">Specimen Tube / Requirements <span class="text-red-500">*</span></label>
                                <div class="flex items-center gap-1.5 text-[11px]">
                                    <button type="button" @click="showNewSpecimenTypeCreate = !showNewSpecimenTypeCreate; specimenTypeError = ''" 
                                            class="text-[#167C78] hover:text-[#136663] font-bold transition">
                                        <span x-show="!showNewSpecimenTypeCreate">+ Add New</span>
                                        <span x-show="showNewSpecimenTypeCreate" class="text-red-600">Cancel</span>
                                    </button>
                                    <span class="text-[#D5DEDF]">•</span>
                                    <button type="button" @click="manageModalOpen = true; manageTab = 'specimen-types'"
                                            class="text-[#61727D] hover:text-[#102A36] font-semibold underline">
                                        Manage
                                    </button>
                                </div>
                            </div>

                            <!-- Inline Add Specimen Tube Form -->
                            <div x-show="showNewSpecimenTypeCreate" x-cloak class="mb-2 p-2.5 bg-[#E8F0EF] border border-[#167C78] space-y-2 rounded-xs">
                                <div class="text-[11px] font-bold text-[#102A36] uppercase tracking-wider flex items-center justify-between">
                                    <span>New Specimen Container</span>
                                    <span class="text-[10px] text-[#61727D] font-normal">Saves instantly to network</span>
                                </div>
                                <div class="space-y-1.5">
                                    <input type="text" x-model="newSpecimenContainer" @keydown.enter.prevent="addNewSpecimenType('create')"
                                           placeholder="Container / Tube (e.g. Light Blue top, Grey top)"
                                           class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                    <input type="text" x-model="newSpecimenName" @keydown.enter.prevent="addNewSpecimenType('create')"
                                           placeholder="Description (optional, e.g. Sodium Citrate Plasma)"
                                           class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                    <div class="grid grid-cols-2 gap-2">
                                        <select x-model="newSpecimenTransport" class="border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                            <option value="ambient">Ambient (15-25°C)</option>
                                            <option value="refrigerated">Refrigerated (2-8°C)</option>
                                            <option value="frozen">Frozen (-20°C)</option>
                                        </select>
                                        <input type="number" x-model="newSpecimenStability" placeholder="Stability (hrs, opt)"
                                               class="border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="text" x-model="newSpecimenCode" @keydown.enter.prevent="addNewSpecimenType('create')"
                                               placeholder="Code (optional, e.g. CITRATE)"
                                               class="w-1/2 border border-[#D5DEDF] p-1.5 text-xs bg-white font-mono uppercase focus:border-[#167C78] focus:outline-none">
                                        <button type="button" @click="addNewSpecimenType('create')" :disabled="savingSpecimenType || !newSpecimenContainer.trim()"
                                                class="w-1/2 py-1.5 px-3 bg-[#167C78] hover:bg-[#136663] disabled:opacity-50 text-white font-bold text-[10px] uppercase tracking-wider transition">
                                            <span x-text="savingSpecimenType ? 'Adding...' : 'Add & Select'"></span>
                                        </button>
                                    </div>
                                    <div x-show="specimenTypeError" x-text="specimenTypeError" class="text-[10px] text-red-600 font-bold"></div>
                                </div>
                            </div>

                            <select name="specimen_type_id" x-model="createSpecimenTypeId"
                                    @change="if($event.target.value === '__add_new__') { showNewSpecimenTypeCreate = true; createSpecimenTypeId = ''; }"
                                    required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                                <option value="">-- Select Specimen Tube --</option>
                                <template x-for="st in specimenTypesList" :key="st.id">
                                    <option :value="st.id" x-text="st.container + ' (' + (st.name || st.container) + ' • ' + st.transport_requirements + ')'"></option>
                                </template>
                                <option value="__add_new__" class="font-bold text-[#167C78] bg-[#E8F0EF]/40">+ Add New Specimen Tube...</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Clinical Description / Indications</label>
                        <textarea name="description" rows="2" placeholder="Clinical purpose, analytes measured, and indications..."
                                  class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Pre-Analytical Patient Preparation Instructions</label>
                        <textarea name="patient_preparation" rows="2" placeholder="e.g. 10-12 hours overnight fasting recommended; avoid biotin supplements 24h prior..."
                                  class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center gap-6 pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="requires_clinical_notes" value="1" class="text-[#167C78] focus:ring-[#167C78]">
                            <span class="font-bold text-[#102A36]">Requires Physician Clinical Notes</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="text-[#167C78] focus:ring-[#167C78]">
                            <span class="font-bold text-[#102A36]">Active in Master Formulary</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="createModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                            Save Master Test
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Test Modal -->
        <div x-show="editModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="editModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-2xl w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Edit Master Diagnostic Test</h3>
                    <button @click="editModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form :action="'/ops/catalogue/' + (currentTest ? currentTest.id : '')" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Standard Test Code <span class="text-red-500">*</span></label>
                            <input type="text" name="code" :value="currentTest?.code" required
                                   class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none font-mono uppercase">
                        </div>

                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">LOINC Standard Code</label>
                            <input type="text" name="loinc_code" :value="currentTest?.loinc_code"
                                   class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Full Diagnostic Test Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" :value="currentTest?.name" required
                               class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Diagnostic Specialty Field in Edit Modal -->
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block font-bold text-[#102A36]">Diagnostic Specialty <span class="text-red-500">*</span></label>
                                <div class="flex items-center gap-1.5 text-[11px]">
                                    <button type="button" @click="showNewSpecialtyEdit = !showNewSpecialtyEdit; specialtyError = ''" 
                                            class="text-[#167C78] hover:text-[#136663] font-bold transition">
                                        <span x-show="!showNewSpecialtyEdit">+ Add New</span>
                                        <span x-show="showNewSpecialtyEdit" class="text-red-600">Cancel</span>
                                    </button>
                                    <span class="text-[#D5DEDF]">•</span>
                                    <button type="button" @click="manageModalOpen = true; manageTab = 'specialties'"
                                            class="text-[#61727D] hover:text-[#102A36] font-semibold underline">
                                        Manage
                                    </button>
                                </div>
                            </div>

                            <!-- Inline Add Specialty Form (Edit Context) -->
                            <div x-show="showNewSpecialtyEdit" x-cloak class="mb-2 p-2.5 bg-[#E8F0EF] border border-[#167C78] space-y-2 rounded-xs">
                                <div class="text-[11px] font-bold text-[#102A36] uppercase tracking-wider flex items-center justify-between">
                                    <span>New Specialty</span>
                                    <span class="text-[10px] text-[#61727D] font-normal">Saves instantly to network</span>
                                </div>
                                <div class="space-y-1.5">
                                    <input type="text" x-model="newSpecialtyName" @keydown.enter.prevent="addNewSpecialty('edit')"
                                           placeholder="Specialty Name (e.g. Immunology & Serology)"
                                           class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                    <div class="flex items-center gap-2">
                                        <input type="text" x-model="newSpecialtyCode" @keydown.enter.prevent="addNewSpecialty('edit')"
                                               placeholder="Code (optional, e.g. IMMUNO)"
                                               class="w-1/2 border border-[#D5DEDF] p-1.5 text-xs bg-white font-mono uppercase focus:border-[#167C78] focus:outline-none">
                                        <button type="button" @click="addNewSpecialty('edit')" :disabled="savingSpecialty || !newSpecialtyName.trim()"
                                                class="w-1/2 py-1.5 px-3 bg-[#167C78] hover:bg-[#136663] disabled:opacity-50 text-white font-bold text-[10px] uppercase tracking-wider transition">
                                            <span x-text="savingSpecialty ? 'Adding...' : 'Add & Select'"></span>
                                        </button>
                                    </div>
                                    <div x-show="specialtyError" x-text="specialtyError" class="text-[10px] text-red-600 font-bold"></div>
                                </div>
                            </div>

                            <select name="diagnostic_specialty_id" x-model="editSpecialtyId"
                                    @change="if($event.target.value === '__add_new__') { showNewSpecialtyEdit = true; editSpecialtyId = ''; }"
                                    required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                                <option value="">-- Select Specialty --</option>
                                <template x-for="spec in specialtiesList" :key="spec.id">
                                    <option :value="spec.id" :selected="editSpecialtyId == spec.id" x-text="spec.name"></option>
                                </template>
                                <option value="__add_new__" class="font-bold text-[#167C78] bg-[#E8F0EF]/40">+ Add New Specialty...</option>
                            </select>
                        </div>

                        <!-- Specimen Tube / Requirements Field in Edit Modal -->
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block font-bold text-[#102A36]">Specimen Tube / Requirements <span class="text-red-500">*</span></label>
                                <div class="flex items-center gap-1.5 text-[11px]">
                                    <button type="button" @click="showNewSpecimenTypeEdit = !showNewSpecimenTypeEdit; specimenTypeError = ''" 
                                            class="text-[#167C78] hover:text-[#136663] font-bold transition">
                                        <span x-show="!showNewSpecimenTypeEdit">+ Add New</span>
                                        <span x-show="showNewSpecimenTypeEdit" class="text-red-600">Cancel</span>
                                    </button>
                                    <span class="text-[#D5DEDF]">•</span>
                                    <button type="button" @click="manageModalOpen = true; manageTab = 'specimen-types'"
                                            class="text-[#61727D] hover:text-[#102A36] font-semibold underline">
                                        Manage
                                    </button>
                                </div>
                            </div>

                            <!-- Inline Add Specimen Tube Form (Edit Context) -->
                            <div x-show="showNewSpecimenTypeEdit" x-cloak class="mb-2 p-2.5 bg-[#E8F0EF] border border-[#167C78] space-y-2 rounded-xs">
                                <div class="text-[11px] font-bold text-[#102A36] uppercase tracking-wider flex items-center justify-between">
                                    <span>New Specimen Container</span>
                                    <span class="text-[10px] text-[#61727D] font-normal">Saves instantly to network</span>
                                </div>
                                <div class="space-y-1.5">
                                    <input type="text" x-model="newSpecimenContainer" @keydown.enter.prevent="addNewSpecimenType('edit')"
                                           placeholder="Container / Tube (e.g. Light Blue top, Grey top)"
                                           class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                    <input type="text" x-model="newSpecimenName" @keydown.enter.prevent="addNewSpecimenType('edit')"
                                           placeholder="Description (optional, e.g. Sodium Citrate Plasma)"
                                           class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                    <div class="grid grid-cols-2 gap-2">
                                        <select x-model="newSpecimenTransport" class="border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                            <option value="ambient">Ambient (15-25°C)</option>
                                            <option value="refrigerated">Refrigerated (2-8°C)</option>
                                            <option value="frozen">Frozen (-20°C)</option>
                                        </select>
                                        <input type="number" x-model="newSpecimenStability" placeholder="Stability (hrs, opt)"
                                               class="border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="text" x-model="newSpecimenCode" @keydown.enter.prevent="addNewSpecimenType('edit')"
                                               placeholder="Code (optional, e.g. CITRATE)"
                                               class="w-1/2 border border-[#D5DEDF] p-1.5 text-xs bg-white font-mono uppercase focus:border-[#167C78] focus:outline-none">
                                        <button type="button" @click="addNewSpecimenType('edit')" :disabled="savingSpecimenType || !newSpecimenContainer.trim()"
                                                class="w-1/2 py-1.5 px-3 bg-[#167C78] hover:bg-[#136663] disabled:opacity-50 text-white font-bold text-[10px] uppercase tracking-wider transition">
                                            <span x-text="savingSpecimenType ? 'Adding...' : 'Add & Select'"></span>
                                        </button>
                                    </div>
                                    <div x-show="specimenTypeError" x-text="specimenTypeError" class="text-[10px] text-red-600 font-bold"></div>
                                </div>
                            </div>

                            <select name="specimen_type_id" x-model="editSpecimenTypeId"
                                    @change="if($event.target.value === '__add_new__') { showNewSpecimenTypeEdit = true; editSpecimenTypeId = ''; }"
                                    required class="w-full border border-[#D5DEDF] p-2 bg-white focus:border-[#167C78] focus:outline-none">
                                <option value="">-- Select Specimen Tube --</option>
                                <template x-for="st in specimenTypesList" :key="st.id">
                                    <option :value="st.id" :selected="editSpecimenTypeId == st.id" x-text="st.container + ' (' + (st.name || st.container) + ' • ' + st.transport_requirements + ')'"></option>
                                </template>
                                <option value="__add_new__" class="font-bold text-[#167C78] bg-[#E8F0EF]/40">+ Add New Specimen Tube...</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Clinical Description / Indications</label>
                        <textarea name="description" rows="2" :value="currentTest?.description"
                                  class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Pre-Analytical Patient Preparation Instructions</label>
                        <textarea name="patient_preparation" rows="2" :value="currentTest?.patient_preparation"
                                  class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center gap-6 pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="requires_clinical_notes" value="1" :checked="currentTest?.requires_clinical_notes" class="text-[#167C78] focus:ring-[#167C78]">
                            <span class="font-bold text-[#102A36]">Requires Physician Clinical Notes</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" :checked="currentTest?.is_active" class="text-[#167C78] focus:ring-[#167C78]">
                            <span class="font-bold text-[#102A36]">Active in Master Formulary</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                            Update Master Test
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Dedicated Management Modal: Manage Specialties & Specimen Tubes -->
        <div x-show="manageModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="manageModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-3xl w-full p-6 space-y-4 shadow-2xl max-h-[90vh] flex flex-col">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <div>
                        <h3 class="text-base font-bold text-[#102A36]">Manage Diagnostic Specialties & Specimen Containers</h3>
                        <p class="text-xs text-[#61727D]">Edit formulations, modify transport temperatures, or remove obsolete types from the network.</p>
                    </div>
                    <button @click="manageModalOpen = false" class="text-gray-400 hover:text-black font-bold text-xl leading-none">&times;</button>
                </div>

                <!-- Tabs -->
                <div class="flex gap-2 border-b border-[#D5DEDF] text-xs font-bold">
                    <button type="button" @click="manageTab = 'specialties'; manageActionError = ''; manageActionSuccess = ''"
                            :class="manageTab === 'specialties' ? 'border-b-2 border-[#167C78] text-[#167C78]' : 'text-[#61727D] hover:text-[#102A36]'"
                            class="pb-2.5 px-3 transition">
                        Diagnostic Specialties (<span x-text="specialtiesList.length"></span>)
                    </button>
                    <button type="button" @click="manageTab = 'specimen-types'; manageActionError = ''; manageActionSuccess = ''"
                            :class="manageTab === 'specimen-types' ? 'border-b-2 border-[#167C78] text-[#167C78]' : 'text-[#61727D] hover:text-[#102A36]'"
                            class="pb-2.5 px-3 transition">
                        Specimen Tubes & Containers (<span x-text="specimenTypesList.length"></span>)
                    </button>
                </div>

                <!-- Alert Messages -->
                <div x-show="manageActionSuccess" x-cloak class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex justify-between items-center">
                    <span x-text="manageActionSuccess"></span>
                    <button type="button" @click="manageActionSuccess = ''" class="font-bold">&times;</button>
                </div>
                <div x-show="manageActionError" x-cloak class="p-3 bg-red-50 border border-red-200 text-red-800 text-xs font-semibold flex justify-between items-center">
                    <span x-text="manageActionError"></span>
                    <button type="button" @click="manageActionError = ''" class="font-bold">&times;</button>
                </div>

                <!-- Tab 1: Specialties List -->
                <div x-show="manageTab === 'specialties'" class="flex-1 overflow-y-auto space-y-2 pr-1">
                    <div class="divide-y divide-[#D5DEDF] border border-[#D5DEDF]">
                        <template x-for="spec in specialtiesList" :key="spec.id">
                            <div class="p-3.5 bg-white hover:bg-[#F5F3ED]/40 transition text-xs flex flex-col gap-2">
                                <!-- View Mode -->
                                <div x-show="editingSpecialtyId !== spec.id" class="flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-[#102A36] text-[13px] flex items-center gap-2">
                                            <span x-text="spec.name"></span>
                                            <span class="font-mono text-[10px] text-[#61727D] bg-[#F5F3ED] px-1.5 py-0.5 border border-[#D5DEDF]" x-text="spec.code"></span>
                                        </div>
                                        <div class="text-[11px] text-[#61727D] mt-0.5">
                                            Usage: <span class="font-semibold text-[#167C78]" x-text="(spec.tests_count || 0) + ' diagnostic test(s) assigned'"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="startEditSpecialty(spec)" class="px-2.5 py-1 text-xs border border-[#D5DEDF] bg-white hover:bg-[#F5F3ED] text-[#102A36] font-semibold">
                                            Edit
                                        </button>
                                        <button type="button" @click="deleteSpecialty(spec)" :disabled="manageActionLoading"
                                                class="px-2.5 py-1 text-xs border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-semibold disabled:opacity-50">
                                            Remove
                                        </button>
                                    </div>
                                </div>

                                <!-- Inline Edit Mode -->
                                <div x-show="editingSpecialtyId === spec.id" x-cloak class="p-3 bg-[#E8F0EF] border border-[#167C78] space-y-2">
                                    <div class="font-bold text-[#102A36] text-[11px] uppercase tracking-wider">Edit Specialty</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-[#61727D] mb-0.5">Name</label>
                                            <input type="text" x-model="editSpecialtyNameInput" class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-[#61727D] mb-0.5">Code</label>
                                            <input type="text" x-model="editSpecialtyCodeInput" class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white font-mono uppercase focus:border-[#167C78] focus:outline-none">
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2 pt-1">
                                        <button type="button" @click="cancelEditSpecialty()" class="px-3 py-1 text-xs border border-[#D5DEDF] bg-white font-semibold text-[#61727D]">
                                            Cancel
                                        </button>
                                        <button type="button" @click="saveEditSpecialty(spec)" :disabled="manageActionLoading || !editSpecialtyNameInput.trim()"
                                                class="px-3 py-1 text-xs bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider disabled:opacity-50">
                                            Save Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Tab 2: Specimen Types List -->
                <div x-show="manageTab === 'specimen-types'" x-cloak class="flex-1 overflow-y-auto space-y-2 pr-1">
                    <div class="divide-y divide-[#D5DEDF] border border-[#D5DEDF]">
                        <template x-for="st in specimenTypesList" :key="st.id">
                            <div class="p-3.5 bg-white hover:bg-[#F5F3ED]/40 transition text-xs flex flex-col gap-2">
                                <!-- View Mode -->
                                <div x-show="editingSpecimenId !== st.id" class="flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-[#102A36] text-[13px] flex items-center gap-2">
                                            <span x-text="st.container"></span>
                                            <span class="text-[11px] font-medium text-[#61727D]" x-show="st.name && st.name !== st.container" x-text="'(' + st.name + ')'"></span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#E8F0EF] text-[#167C78]" x-text="st.transport_requirements || 'ambient'"></span>
                                        </div>
                                        <div class="text-[11px] text-[#61727D] mt-0.5 flex items-center gap-3">
                                            <span x-show="st.stability_hours" x-text="'Stability: ' + st.stability_hours + ' hrs'"></span>
                                            <span class="font-mono text-[10px]" x-show="st.code" x-text="'Code: ' + st.code"></span>
                                            <span>Usage: <strong class="text-[#167C78]" x-text="(st.tests_count || 0) + ' test(s) require this tube'"></strong></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="startEditSpecimenType(st)" class="px-2.5 py-1 text-xs border border-[#D5DEDF] bg-white hover:bg-[#F5F3ED] text-[#102A36] font-semibold">
                                            Edit
                                        </button>
                                        <button type="button" @click="deleteSpecimenType(st)" :disabled="manageActionLoading"
                                                class="px-2.5 py-1 text-xs border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-semibold disabled:opacity-50">
                                            Remove
                                        </button>
                                    </div>
                                </div>

                                <!-- Inline Edit Mode -->
                                <div x-show="editingSpecimenId === st.id" x-cloak class="p-3 bg-[#E8F0EF] border border-[#167C78] space-y-2">
                                    <div class="font-bold text-[#102A36] text-[11px] uppercase tracking-wider">Edit Specimen Container</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-[#61727D] mb-0.5">Container / Tube</label>
                                            <input type="text" x-model="editSpecimenContainerInput" class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-[#61727D] mb-0.5">Description</label>
                                            <input type="text" x-model="editSpecimenNameInput" class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-[#61727D] mb-0.5">Transport Condition</label>
                                            <select x-model="editSpecimenTransportInput" class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                                <option value="ambient">Ambient (15-25°C)</option>
                                                <option value="refrigerated">Refrigerated (2-8°C)</option>
                                                <option value="frozen">Frozen (-20°C)</option>
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-[10px] font-bold text-[#61727D] mb-0.5">Stability (hrs)</label>
                                                <input type="number" x-model="editSpecimenStabilityInput" class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white focus:border-[#167C78] focus:outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-[#61727D] mb-0.5">Code</label>
                                                <input type="text" x-model="editSpecimenCodeInput" class="w-full border border-[#D5DEDF] p-1.5 text-xs bg-white font-mono uppercase focus:border-[#167C78] focus:outline-none">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2 pt-1">
                                        <button type="button" @click="cancelEditSpecimenType()" class="px-3 py-1 text-xs border border-[#D5DEDF] bg-white font-semibold text-[#61727D]">
                                            Cancel
                                        </button>
                                        <button type="button" @click="saveEditSpecimenType(st)" :disabled="manageActionLoading || !editSpecimenContainerInput.trim()"
                                                class="px-3 py-1 text-xs bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider disabled:opacity-50">
                                            Save Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pt-3 border-t border-[#D5DEDF] flex justify-end">
                    <button type="button" @click="manageModalOpen = false" class="px-4 py-2 bg-[#102A36] text-white text-xs font-bold hover:bg-[#193B49] transition uppercase">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.workspace>
