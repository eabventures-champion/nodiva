<x-layouts.workspace title="Institutional Partners & User Governance — NODIVA Operations" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="partners" />
    </x-slot:sidebar>

    @php
        $nonPlatformIds = $partners->reject(fn ($p) => $p->isPlatform())->pluck('id')->values()->all();
        $allPagePartnerIds = $partners->pluck('id')->values()->all();
    @endphp

    <div class="space-y-6" x-data="partnersDirectory({{ json_encode($nonPlatformIds) }}, {{ json_encode($allPagePartnerIds) }}, {{ json_encode($deletableUserIds ?? []) }}, {{ json_encode($searchQuery ?? '') }}, {{ json_encode($currentType ?? '') }}, {{ json_encode($currentStatus ?? '') }})">
        <!-- Page Header & Key Metrics -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-[#102A36]">Institutional Partner Directory</h1>
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-xs bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/30">
                        CTO Governance Console
                    </span>
                </div>
                <p class="text-xs text-[#61727D]">
                    Hospitals, accredited laboratories, and corporate employers matched to their respective organizations on NODIVA.
                </p>
            </div>

            <!-- Status Filter Tabs -->
            <div id="status-filter-tabs" class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                <a href="{{ route('ops.partners.index', array_filter(['type' => $currentType, 'q' => $searchQuery])) }}" 
                   @click.prevent="filterByStatus('')"
                   class="px-3 py-1.5 rounded-sm border transition {{ empty($currentStatus) ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#E8F0EF]' }}">
                    All Statuses ({{ $counts['total'] }})
                </a>
                <a href="{{ route('ops.partners.index', array_filter(['status' => 'pending', 'type' => $currentType, 'q' => $searchQuery])) }}" 
                   @click.prevent="filterByStatus('pending')"
                   class="px-3 py-1.5 rounded-sm border transition {{ $currentStatus === 'pending' ? 'bg-[#94611A] text-white border-[#94611A]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#E8F0EF]' }}">
                    Pending Review ({{ $counts['pending'] }})
                </a>
                <a href="{{ route('ops.partners.index', array_filter(['status' => 'active', 'type' => $currentType, 'q' => $searchQuery])) }}" 
                   @click.prevent="filterByStatus('active')"
                   class="px-3 py-1.5 rounded-sm border transition {{ $currentStatus === 'active' ? 'bg-[#247451] text-white border-[#247451]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#E8F0EF]' }}">
                    Active ({{ $counts['active'] }})
                </a>
                <a href="{{ route('ops.partners.index', array_filter(['status' => 'rejected', 'type' => $currentType, 'q' => $searchQuery])) }}" 
                   @click.prevent="filterByStatus('rejected')"
                   class="px-3 py-1.5 rounded-sm border transition {{ $currentStatus === 'rejected' ? 'bg-[#B42332] text-white border-[#B42332]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:bg-[#E8F0EF]' }}">
                    Rejected ({{ $counts['rejected'] }})
                </a>
            </div>
        </div>


        <!-- Quick Metrics & View Mode Switcher -->
        <div id="quick-metrics-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white border border-[#D5DEDF] p-3.5 flex items-center justify-between">
                <div>
                    <div class="text-[10px] uppercase font-bold text-[#61727D] tracking-wider">Total Organizations</div>
                    <div class="text-xl font-bold text-[#102A36] mt-0.5">{{ $counts['total'] }}</div>
                </div>
                <div class="p-2 bg-[#167C78]/10 text-[#167C78] rounded-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>

            <div class="bg-white border border-[#D5DEDF] p-3.5 flex items-center justify-between">
                <div>
                    <div class="text-[10px] uppercase font-bold text-[#61727D] tracking-wider">Registered Users</div>
                    <div class="text-xl font-bold text-[#167C78] mt-0.5">{{ $counts['total_users'] }}</div>
                </div>
                <div class="p-2 bg-[#167C78]/10 text-[#167C78] rounded-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white border border-[#D5DEDF] p-3.5 flex items-center justify-between">
                <div>
                    <div class="text-[10px] uppercase font-bold text-[#61727D] tracking-wider">Active Memberships</div>
                    <div class="text-xl font-bold text-emerald-700 mt-0.5">{{ $counts['total_memberships'] }}</div>
                </div>
                <div class="p-2 bg-emerald-50 text-emerald-700 rounded-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white border border-[#D5DEDF] p-3.5 flex items-center justify-between">
                <div>
                    <div class="text-[10px] uppercase font-bold text-[#61727D] tracking-wider">Current Authority</div>
                    <div class="text-xs font-bold text-[#102A36] mt-1 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ auth()->user()?->job_title ?? 'Chief Technical Officer' }}</span>
                    </div>
                </div>
                <div class="p-2 bg-amber-50 text-amber-700 rounded-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </div>
            </div>
        </div>

        @if ($unassignedUsers->isNotEmpty())
            <!-- Unassigned Users Alert -->
            <div class="p-4 bg-amber-50 border border-amber-300 text-amber-900 text-xs rounded-xs space-y-2 shadow-2xs">
                <div class="font-bold flex items-center gap-1.5 text-amber-800">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Notice: {{ $unassignedUsers->count() }} Platform User(s) Without Organization Membership</span>
                </div>
                <p class="text-[11px] text-amber-800/90 leading-relaxed">
                    The following user accounts exist on the platform but have not been matched to an active organization:
                </p>
                <div class="flex flex-wrap gap-2 pt-1">
                    @foreach ($unassignedUsers as $unUser)
                        <div class="inline-flex items-center gap-2 px-2.5 py-1 bg-white border border-amber-200 rounded-xs text-[11px]">
                            <span class="font-bold text-[#102A36]">{{ $unUser->name }}</span>
                            <span class="text-[#61727D] font-mono text-[10px]">({{ $unUser->email }})</span>
                            <span class="text-[#8A9BA8] text-[10px]">{{ $unUser->job_title ?? 'Unassigned' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Filter & Search Bar -->
        <div class="bg-white border border-[#D5DEDF] p-4 flex flex-col md:flex-row gap-3 justify-between items-center shadow-xs">
            <form method="GET" action="{{ route('ops.partners.index') }}" @submit.prevent="performSearch()" class="flex-1 flex flex-col sm:flex-row gap-3 w-full">
                @if ($currentStatus)
                    <input type="hidden" name="status" :value="currentStatus">
                @endif
                <div class="relative flex-1">
                    <input type="text" 
                           name="q" 
                           x-model="searchQuery"
                           @input="triggerLiveSearch()"
                           @keydown.escape="clearSearch()"
                           autocomplete="off"
                           placeholder="Search by organization, user name, email, job title, or role..."
                           class="w-full text-xs border border-[#D5DEDF] pl-9 pr-9 py-2 focus:border-[#167C78] focus:outline-none bg-white transition">
                    <svg class="w-4 h-4 text-[#8A9BA8] absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>

                    <!-- Live Loading Spinner -->
                    <div x-show="isLoading" x-cloak class="absolute right-3 top-2.5">
                        <svg class="animate-spin h-4 w-4 text-[#167C78]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </div>

                    <!-- Clear search query button -->
                    <button type="button" 
                            x-show="!isLoading && searchQuery" 
                            @click="clearSearch()" 
                            class="absolute right-2.5 top-2 text-[#8A9BA8] hover:text-[#102A36] p-0.5 rounded-full transition" 
                            title="Clear search query">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Type Selector -->
                <select name="type" 
                        x-model="selectedType" 
                        @change="performSearch()"
                        class="text-xs border border-[#D5DEDF] px-3 py-2 bg-white focus:border-[#167C78] focus:outline-none font-medium transition cursor-pointer">
                    <option value="">All Partner Types (Hospitals, Labs, Corporate)</option>
                    <option value="hospital">Referring Hospitals</option>
                    <option value="laboratory">Testing Laboratories</option>
                    <option value="corporate">Corporate Clients</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-[#102A36] hover:bg-[#193B49] text-white text-xs font-bold transition shadow-2xs flex items-center justify-center gap-1.5 shrink-0">
                    <span x-show="!isLoading">Search &amp; Filter</span>
                    <span x-show="isLoading" x-cloak>Searching...</span>
                </button>

                <template x-if="searchQuery || selectedType || currentStatus">
                    <button type="button" 
                            @click="clearAllFilters()" 
                            class="px-3 py-2 border border-[#D5DEDF] text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#102A36] text-xs font-bold flex items-center justify-center transition shrink-0">
                        Reset All
                    </button>
                </template>
            </form>
        </div>

        <!-- View Switcher Tabs & Table Actions Bar -->
        <div id="view-switcher-tabs" class="flex flex-wrap items-center justify-between gap-3 border-b border-[#D5DEDF] pb-2">
            <div class="flex items-center gap-1 text-xs font-bold">
                <button type="button" 
                        @click="activeTab = 'organizations'"
                        :class="activeTab === 'organizations' ? 'bg-[#102A36] text-white' : 'bg-white text-[#61727D] border border-[#D5DEDF] hover:bg-[#E8F0EF]'"
                        class="px-3.5 py-1.5 rounded-xs transition flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>Matched by Organization ({{ $partners->total() }})</span>
                </button>
                <button type="button" 
                        @click="activeTab = 'users'"
                        :class="activeTab === 'users' ? 'bg-[#102A36] text-white' : 'bg-white text-[#61727D] border border-[#D5DEDF] hover:bg-[#E8F0EF]'"
                        class="px-3.5 py-1.5 rounded-xs transition flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Partner Personnel Directory ({{ $allPlatformUsers->count() }})</span>
                </button>
            </div>

            <!-- Expand / Collapse All Roster Toggle -->
            <div x-show="activeTab === 'organizations'" class="flex items-center gap-2">
                <button type="button" 
                        @click="toggleAllOrgs()"
                        class="px-3 py-1 bg-white hover:bg-[#E8F0EF] border border-[#D5DEDF] text-[#102A36] text-xs font-bold rounded-xs transition flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                    </svg>
                    <span x-text="expandedOrgs.length === allOrgIds.length ? 'Collapse All User Rosters' : 'Expand All User Rosters'"></span>
                </button>
            </div>
        </div>

        <!-- TAB 1: Matched by Organization -->
        <div x-show="activeTab === 'organizations'" id="organizations-container" class="bg-white border border-[#D5DEDF]">
            <div id="deletable-ids-data" class="hidden" 
                 data-non-platform-ids="{{ json_encode($nonPlatformIds) }}" 
                 data-all-org-ids="{{ json_encode($allPagePartnerIds) }}"></div>
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex flex-wrap justify-between items-center gap-3 bg-[#F5F3ED]/30">
                <div class="flex items-center gap-3">
                    <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Organizations &amp; Matched Personnel</h2>
                    <span class="text-xs text-[#61727D] font-mono">Showing {{ $partners->total() }} partner institutions</span>
                </div>

                <div class="flex items-center gap-2">
                    @if ($isCto)
                        <!-- Delete Selected Action (visible when 1 or more checked) -->
                        <div x-show="selectedIds.length > 0" x-cloak class="inline-flex items-center">
                            <button type="button" @click="openDeleteAllModal()"
                                    class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold uppercase tracking-wider rounded-xs flex items-center gap-1.5 shadow-sm transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span x-text="'Delete Selected (' + selectedIds.length + ')'"></span>
                            </button>
                        </div>

                        <!-- Delete All Action -->
                        @if ($partners->total() > 0 && count($nonPlatformIds) > 0)
                            <button type="button" @click="selectedIds = []; openDeleteAllModal()"
                                    class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-700 hover:text-rose-900 text-xs font-bold uppercase tracking-wider rounded-xs flex items-center gap-1.5 transition shadow-2xs"
                                    title="Delete registered partner institutions and team members (Platform Ops protected)">
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete All Partners
                            </button>
                        @endif
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold text-[10px] tracking-wider">
                        <tr>
                            @if ($isCto)
                                <th class="w-8 px-4 py-3 text-center">
                                    <input type="checkbox" x-model="selectAll" @change="toggleSelectAll" class="rounded-xs border-[#D5DEDF] text-[#167C78] focus:ring-[#167C78]">
                                </th>
                            @endif
                            <th class="px-6 py-3 min-w-[260px]">Institution Name &amp; Licence</th>
                            <th class="px-6 py-3 min-w-[320px]">Matched Users &amp; Roles</th>
                            <th class="px-6 py-3 text-center whitespace-nowrap">Status</th>
                            <th class="px-6 py-3 text-right whitespace-nowrap">Verification Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($partners as $partner)
                            @php
                                $adminMembership = $partner->memberships->first(fn ($m) => $m->role?->is_admin) ?? $partner->memberships->first();
                                $leadUser = $adminMembership?->user;
                                $adminEmail = $leadUser?->email;
                                $isPlatformOrg = $partner->isPlatform();
                                $memberCount = $partner->memberships->count();
                            @endphp
                            <!-- Main Organization Row -->
                            <tr class="hover:bg-[#E8F0EF]/30 transition" :class="selectedIds.includes({{ $partner->id }}) ? 'bg-rose-50/40' : ''">
                                @if ($isCto)
                                    <td class="w-8 px-4 py-3.5 text-center align-top">
                                        @if (! $isPlatformOrg)
                                            <input type="checkbox" :value="{{ $partner->id }}" x-model="selectedIds" @change="updateSelectAllState" class="rounded-xs border-[#D5DEDF] text-[#167C78] focus:ring-[#167C78]">
                                        @else
                                            <span title="Platform Operations is core infrastructure and cannot be deleted">
                                                <svg class="w-3.5 h-3.5 text-[#8A9BA8] mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                </svg>
                                            </span>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-6 py-3.5 align-top">
                                    <div class="space-y-1.5">
                                        <div class="font-bold text-[#102A36] text-sm leading-snug flex items-center gap-1.5">
                                            <span>{{ $partner->name }}</span>
                                            @if ($isPlatformOrg)
                                                <span class="px-1.5 py-0.2 bg-[#102A36] text-white text-[9px] font-bold rounded-xs uppercase tracking-wider">
                                                    Platform
                                                </span>
                                            @endif
                                        </div>
                                        
                                        @php
                                            $licenceDisplay = $partner->registration_number;
                                            if (! $licenceDisplay && $partner->type->value === 'laboratory') {
                                                $licenceDisplay = $partner->qualifications->where('status', 'verified')->first()?->reference_number;
                                            }
                                        @endphp
                                        <div class="flex items-center gap-1.5 pt-0.5 flex-wrap">
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded-xs tracking-wider inline-block
                                                @if ($partner->type->value === 'hospital') bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/30
                                                @elseif ($partner->type->value === 'laboratory') bg-[#102A36]/10 text-[#102A36] border border-[#102A36]/30
                                                @elseif ($partner->type->value === 'platform') bg-purple-100 text-purple-800 border border-purple-300
                                                @else bg-[#94611A]/10 text-[#94611A] border border-[#94611A]/30 @endif">
                                                {{ $partner->type->value }}
                                            </span>

                                            @if ($licenceDisplay)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#E8F0EF] text-[#167C78] border border-[#167C78]/25 rounded-xs text-[10px] font-mono font-bold tracking-tight whitespace-nowrap"
                                                      title="Official Operating Licence">
                                                    <svg class="w-3 h-3 text-[#167C78] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                    </svg>
                                                    <span>{{ $licenceDisplay }}</span>
                                                </span>
                                            @elseif ($partner->status->value === 'active')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/25 rounded-xs text-[10px] font-semibold whitespace-nowrap">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Accredited &amp; Verified
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#94611A]/10 text-[#94611A] border border-[#94611A]/25 rounded-xs text-[10px] font-semibold whitespace-nowrap">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    Licence Pending Submission
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-[10px] text-[#61727D] font-mono flex items-center gap-1 pt-0.5" title="System Reference UUID: {{ $partner->uuid }}">
                                            <span class="text-[9px] uppercase tracking-wider text-[#8A9BA8] font-sans font-bold">Ref:</span>
                                            <span class="text-[#61727D] truncate max-w-[210px]">{{ $partner->uuid }}</span>
                                        </div>

                                        <!-- Service Region placed under Institution Name & Licence -->
                                        <div class="flex items-center gap-1.5 text-[11px] text-[#61727D] pt-0.5">
                                            <svg class="w-3.5 h-3.5 text-[#167C78] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="font-medium text-[#102A36]">{{ $partner->city ?? 'Accra' }}</span>
                                            <span class="text-[#8A9BA8]">•</span>
                                            <span>{{ $partner->region?->name ?? 'Greater Accra Region' }}</span>
                                        </div>

                                        <!-- Toggle Roster Quick Button -->
                                        <div class="pt-1">
                                            <button type="button" 
                                                    @click="toggleOrg({{ $partner->id }})"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold rounded-xs transition border shadow-2xs"
                                                    :class="expandedOrgs.includes({{ $partner->id }}) ? 'bg-[#102A36] text-white border-[#102A36]' : 'bg-[#E8F0EF] text-[#167C78] border-[#167C78]/30 hover:bg-[#d8e7e5]'">
                                                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="expandedOrgs.includes({{ $partner->id }}) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                                <span x-text="expandedOrgs.includes({{ $partner->id }}) ? 'Hide Personnel Roster ({{ $memberCount }})' : 'View All Users & Roles ({{ $memberCount }})'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 align-top">
                                    <div class="space-y-2">
                                        <!-- Primary Contact -->
                                        <div class="text-xs">
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                                <span class="font-semibold text-[#102A36]">{{ $partner->email }}</span>
                                                @if ($partner->phone)
                                                    <span class="text-[#8A9BA8] hidden sm:inline">•</span>
                                                    <span class="text-[#61727D] font-mono text-[11px] whitespace-nowrap">{{ $partner->phone }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Matched Users Summary Chips -->
                                        <div class="space-y-1.5">
                                            <div class="text-[10px] font-bold uppercase tracking-wider text-[#61727D] flex items-center justify-between">
                                                <span>Matched Platform Users ({{ $memberCount }}):</span>
                                            </div>

                                            <div class="space-y-1">
                                                @forelse ($partner->memberships as $membership)
                                                    @php $mUser = $membership->user; @endphp
                                                    @if ($mUser)
                                                        <div class="flex items-center justify-between gap-2 p-1.5 bg-[#F5F3ED]/80 rounded-xs border border-[#D5DEDF]/70">
                                                            <div class="min-w-0">
                                                                <div class="font-bold text-[#102A36] text-[11px] truncate flex items-center gap-1.5">
                                                                    <span>{{ $mUser->name }}</span>
                                                                    @if ($mUser->isCto())
                                                                        <span class="px-1 py-0.2 bg-amber-500 text-white text-[8px] font-bold rounded-xs uppercase">CTO</span>
                                                                    @endif
                                                                </div>
                                                                <div class="text-[10px] text-[#61727D] font-mono truncate">{{ $mUser->email }}</div>
                                                            </div>
                                                            <div class="shrink-0 flex items-center gap-1">
                                                                <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-xs tracking-tight whitespace-nowrap
                                                                    @if ($membership->role?->is_admin) bg-emerald-100 text-emerald-800 border border-emerald-300
                                                                    @elseif (str_contains($membership->role?->key ?? '', 'pathologist') || str_contains($membership->role?->key ?? '', 'scientist')) bg-indigo-100 text-indigo-800 border border-indigo-200
                                                                    @elseif (str_contains($membership->role?->key ?? '', 'clinician') || str_contains($membership->role?->key ?? '', 'doctor')) bg-sky-100 text-sky-800 border border-sky-200
                                                                    @elseif (str_contains($membership->role?->key ?? '', 'finance')) bg-amber-100 text-amber-800 border border-amber-300
                                                                    @else bg-slate-100 text-slate-700 border border-slate-300 @endif">
                                                                    {{ $membership->role?->name ?? 'Standard Role' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @empty
                                                    <div class="text-[11px] text-[#8A9BA8] italic p-1.5 bg-[#F5F3ED]/40 border border-[#D5DEDF]/40 rounded-xs">
                                                        No personnel currently assigned to this organization.
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 text-center align-top">
                                    <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase rounded-sm inline-block
                                        @if ($partner->status->value === 'active') bg-[#247451]/10 text-[#247451] border border-[#247451]/30
                                        @elseif ($partner->status->value === 'pending') bg-[#94611A]/10 text-[#94611A] border border-[#94611A]/30
                                        @elseif ($partner->status->value === 'suspended') bg-amber-100 text-amber-800 border border-amber-300
                                        @else bg-[#B42332]/10 text-[#B42332] border border-[#B42332]/30 @endif">
                                        {{ $partner->status->label() }}
                                    </span>
                                    @if ($partner->status->value === 'active' && $partner->approved_at)
                                        <div class="text-[9px] text-[#61727D] mt-0.5">Confirmed {{ $partner->approved_at->format('M d, Y') }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right font-medium align-top">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        @if (! $isPlatformOrg)
                                            @if ($partner->status->value === 'pending')
                                                <button type="button" @click="openStatusModal({{ json_encode($partner) }}, 'active')"
                                                        title="Confirm & Activate Institution"
                                                        class="p-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-700 rounded-xs transition shadow-2xs flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                                <button type="button" @click="openStatusModal({{ json_encode($partner) }}, 'rejected')"
                                                        title="Decline Access Request"
                                                        class="p-1.5 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 rounded-xs transition shadow-2xs flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            @elseif ($partner->status->value === 'active')
                                                <!-- Suspend Icon Button -->
                                                <button type="button" @click="openStatusModal({{ json_encode($partner) }}, 'suspended')"
                                                        title="Suspend Institution Access"
                                                        class="p-1.5 bg-white hover:bg-amber-50 border border-[#D5DEDF] hover:border-amber-300 text-[#61727D] hover:text-amber-800 rounded-xs transition shadow-2xs flex items-center justify-center group">
                                                    <svg class="w-4 h-4 text-[#61727D] group-hover:text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </button>
                                            @else
                                                <!-- Reactivate Icon Button -->
                                                <button type="button" @click="openStatusModal({{ json_encode($partner) }}, 'active')"
                                                        title="Reactivate Institution"
                                                        class="p-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-700 rounded-xs transition shadow-2xs flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        @endif

                                        <!-- Impersonate Primary Administrator (CTO Privilege) -->
                                        @if ($isCto && $leadUser && auth()->id() !== $leadUser->id)
                                            <form method="POST" action="{{ route('ops.impersonate.user', $leadUser) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        title="Impersonate Primary Lead / Admin ({{ $leadUser->name }})"
                                                        class="p-1.5 bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-800 rounded-xs transition shadow-2xs flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Delete Icon Button (Only Chief Technical Officer; Platform Org Protected) -->
                                        @if ($isCto)
                                            @if (! $isPlatformOrg)
                                                <button type="button" 
                                                        @click="openDeleteModal({{ json_encode($partner) }})"
                                                        title="Delete Institution &amp; Associated Team Members (CTO Privilege)"
                                                        class="p-1.5 bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-700 hover:text-rose-900 rounded-xs transition shadow-2xs flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            @else
                                                <button type="button" disabled
                                                        title="Core Platform Operations cannot be deleted"
                                                        class="p-1.5 bg-gray-50 border border-gray-200 text-gray-400 rounded-xs cursor-not-allowed opacity-60 flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Expandable Personnel & Roles Sub-Table Drawer -->
                            <tr x-show="expandedOrgs.includes({{ $partner->id }})" x-cloak class="bg-gradient-to-b from-[#F5F8F8] to-[#EEF4F3] border-b border-[#D5DEDF]">
                                <td colspan="{{ $isCto ? 5 : 4 }}" class="p-3 sm:p-5">
                                    <div class="bg-white border border-[#D5DEDF] rounded-xs shadow-xs p-4 space-y-3">
                                        <!-- Drawer Header -->
                                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#D5DEDF] pb-2.5">
                                            <div class="flex items-center gap-2">
                                                <span class="p-1.5 bg-[#167C78]/10 text-[#167C78] rounded-xs">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                </span>
                                                <div>
                                                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#102A36]">
                                                        Personnel &amp; Roles Roster — {{ $partner->name }}
                                                    </h4>
                                                    <p class="text-[10px] text-[#61727D]">
                                                        All user accounts matched under this organization with their active system roles, credentials, and CTO governance actions.
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-mono px-2 py-0.5 bg-[#E8F0EF] text-[#167C78] font-bold rounded-xs border border-[#167C78]/25">
                                                    {{ $memberCount }} {{ Str::plural('User', $memberCount) }} Matched
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Detailed Members Table -->
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-xs border border-[#E8ECEC]">
                                                <thead class="bg-[#F5F3ED] text-[#61727D] uppercase font-semibold text-[9px] tracking-wider border-b border-[#E8ECEC]">
                                                    <tr>
                                                        <th class="px-3.5 py-2 min-w-[220px]">User, Title &amp; Contact</th>
                                                        <th class="px-3.5 py-2">Assigned Role &amp; Status</th>
                                                        <th class="px-3.5 py-2">Department</th>
                                                        <th class="px-3.5 py-2 text-right">CTO Governance</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-[#E8ECEC]">
                                                    @forelse ($partner->memberships as $membership)
                                                        @php $memberUser = $membership->user; @endphp
                                                        @if ($memberUser)
                                                            <tr class="hover:bg-[#F5F8F8] transition">
                                                                <td class="px-3.5 py-2.5">
                                                                    <div class="flex items-start gap-2.5">
                                                                        <div class="w-7 h-7 rounded-full bg-[#102A36] text-white flex items-center justify-center font-bold text-[10px] shrink-0 uppercase tracking-tighter mt-0.5">
                                                                            {{ substr($memberUser->name, 0, 2) }}
                                                                        </div>
                                                                        <div class="space-y-0.5">
                                                                            <div class="font-bold text-[#102A36] text-xs flex items-center gap-1.5 flex-wrap">
                                                                                <span>{{ $memberUser->name }}</span>
                                                                                @if ($memberUser->isCto())
                                                                                    <span class="px-1.5 py-0.2 bg-amber-500 text-white text-[8px] font-bold rounded-xs uppercase">CTO</span>
                                                                                @elseif ($membership->role?->is_admin)
                                                                                    <span class="px-1.5 py-0.2 bg-emerald-600 text-white text-[8px] font-bold rounded-xs uppercase">Admin</span>
                                                                                @endif
                                                                            </div>
                                                                            <div class="text-[10px] text-[#61727D]">
                                                                                {{ $memberUser->job_title ?? 'Platform User' }}
                                                                            </div>
                                                                            <!-- Sign-In Email & Contact placed under User & Job Title -->
                                                                            <div class="flex items-center gap-1 text-[11px] font-mono text-[#102A36] pt-0.5">
                                                                                <svg class="w-3 h-3 text-[#167C78] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                                                                </svg>
                                                                                <span>{{ $memberUser->email }}</span>
                                                                                @if ($memberUser->phone)
                                                                                    <span class="text-[#8A9BA8] font-sans">•</span>
                                                                                    <span class="text-[#61727D]">{{ $memberUser->phone }}</span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td class="px-3.5 py-2.5">
                                                                    <div class="space-y-1">
                                                                        <div>
                                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-xs text-[10px] font-bold tracking-tight
                                                                                @if ($membership->role?->is_admin) bg-emerald-100 text-emerald-800 border border-emerald-300
                                                                                @elseif (str_contains($membership->role?->key ?? '', 'pathologist') || str_contains($membership->role?->key ?? '', 'scientist')) bg-indigo-100 text-indigo-800 border border-indigo-200
                                                                                @elseif (str_contains($membership->role?->key ?? '', 'clinician') || str_contains($membership->role?->key ?? '', 'doctor')) bg-sky-100 text-sky-800 border border-sky-200
                                                                                @elseif (str_contains($membership->role?->key ?? '', 'finance')) bg-amber-100 text-amber-800 border border-amber-300
                                                                                @else bg-slate-100 text-slate-800 border border-slate-300 @endif">
                                                                                @if ($membership->role?->is_admin)
                                                                                    <svg class="w-2.5 h-2.5 text-emerald-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                                                @endif
                                                                                {{ $membership->role?->name ?? 'Standard Role' }}
                                                                            </span>
                                                                        </div>
                                                                        <div>
                                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase
                                                                                @if ($membership->status === 'active') bg-emerald-50 text-emerald-700 border border-emerald-200
                                                                                @elseif ($membership->status === 'suspended') bg-amber-50 text-amber-700 border border-amber-200
                                                                                @else bg-gray-50 text-gray-600 border border-gray-200 @endif">
                                                                                <span class="w-1.5 h-1.5 rounded-full {{ $membership->status === 'active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                                                                {{ ucfirst($membership->status) }}
                                                                            </span>
                                                                        </div>
                                                                        @if ($membership->role?->description)
                                                                            <div class="text-[9px] text-[#61727D] truncate max-w-[240px]" title="{{ $membership->role->description }}">
                                                                                {{ $membership->role->description }}
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td class="px-3.5 py-2.5 text-[#61727D] text-[11px]">
                                                                    {{ $membership->department?->name ?? 'General Practice' }}
                                                                </td>
                                                                <td class="px-3.5 py-2.5 text-right whitespace-nowrap">
                                                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                                                        @if ($isCto)
                                                                            <!-- Impersonate User Button -->
                                                                            @if (auth()->id() !== $memberUser->id)
                                                                                <form method="POST" action="{{ route('ops.impersonate.user', $memberUser) }}" class="inline">
                                                                                    @csrf
                                                                                    <button type="submit" 
                                                                                            title="Impersonate {{ $memberUser->name }} ({{ $membership->role?->name }})"
                                                                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-800 rounded-xs text-[10px] font-bold transition flex items-center gap-1 shadow-2xs">
                                                                                        <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                                                        </svg>
                                                                                        <span>Impersonate</span>
                                                                                    </button>
                                                                                </form>
                                                                            @else
                                                                                <span class="px-2 py-1 bg-gray-100 text-[#8A9BA8] border border-gray-200 rounded-xs text-[10px] font-medium italic">Active Session</span>
                                                                            @endif

                                                                            <!-- Revoke / Remove Member Button -->
                                                                            @if (! $memberUser->isCto() && ! $isPlatformOrg)
                                                                                <button type="button"
                                                                                        @click="openDeleteMemberModal({{ json_encode($partner) }}, {{ json_encode($memberUser) }}, {{ $membership->id }})"
                                                                                        title="Remove {{ $memberUser->name }} from {{ $partner->name }}"
                                                                                        class="p-1 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 rounded-xs transition shadow-2xs flex items-center justify-center">
                                                                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                                    </svg>
                                                                                </button>
                                                                            @endif
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="px-3.5 py-4 text-center text-[#61727D] italic bg-[#F5F3ED]/20">
                                                                No personnel are currently assigned to this organization.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isCto ? 5 : 4 }}" class="px-6 py-12 text-center text-[#61727D]">
                                    No partner institutions matching the filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($partners->hasPages())
                <div class="px-6 py-3 border-t border-[#D5DEDF] bg-[#F5F3ED]">
                    {{ $partners->links() }}
                </div>
            @endif
        </div>

        <!-- TAB 2: Unified Platform Users Directory -->
        <div x-show="activeTab === 'users'" x-cloak id="users-container" class="bg-white border border-[#D5DEDF]">
            <div id="deletable-user-ids-data" class="hidden" 
                 data-deletable-user-ids="{{ json_encode($deletableUserIds ?? []) }}"></div>
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex flex-wrap justify-between items-center gap-3 bg-[#F5F3ED]/30">
                <div class="flex items-center gap-3">
                    <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Partner Personnel Directory</h2>
                    <span class="text-xs text-[#61727D] font-mono">Showing all {{ $allPlatformUsers->count() }} accounts registered across partner institutions</span>
                </div>

                <div class="flex items-center gap-2">
                    @if ($isCto)
                        <!-- Delete Selected Users Action (visible when 1 or more checked) -->
                        <div x-show="selectedUserIds.length > 0" x-cloak class="inline-flex items-center">
                            <button type="button" @click="openDeleteAllUsersModal()"
                                    class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold uppercase tracking-wider rounded-xs flex items-center gap-1.5 shadow-sm transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span x-text="'Delete Selected (' + selectedUserIds.length + ')'"></span>
                            </button>
                        </div>

                        <!-- Delete All Users Action -->
                        @if (!empty($deletableUserIds))
                            <button type="button" @click="selectedUserIds = []; openDeleteAllUsersModal()"
                                    class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-700 hover:text-rose-900 text-xs font-bold uppercase tracking-wider rounded-xs flex items-center gap-1.5 transition shadow-2xs"
                                    title="Delete all registered platform users (Chief Technical Officer session protected)">
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete All Users
                            </button>
                        @endif
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold text-[10px] tracking-wider">
                        <tr>
                            @if ($isCto)
                                <th class="w-10 px-4 py-3 text-center">
                                    <input type="checkbox" 
                                           x-model="selectAllUsers" 
                                           @change="toggleSelectAllUsers()"
                                           class="rounded-xs border-[#D5DEDF] text-[#167C78] focus:ring-[#167C78] cursor-pointer"
                                           title="Select all deletable users">
                                </th>
                            @endif
                            <th class="px-6 py-3 min-w-[240px]">User, Title &amp; Contact</th>
                            <th class="px-6 py-3 min-w-[220px]">Matched Organization</th>
                            <th class="px-6 py-3 min-w-[200px]">Assigned Role &amp; Status</th>
                            <th class="px-6 py-3 text-right whitespace-nowrap">Governance Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($allPlatformUsers as $user)
                            @php
                                $membership = $user->memberships->first();
                                $userOrg = $membership?->organization;
                                $userRole = $membership?->role;
                                $isDeletable = ! $user->isCto() && $user->id !== auth()->id();
                            @endphp
                            <tr class="hover:bg-[#E8F0EF]/30 transition {{ $user->id === auth()->id() ? 'bg-amber-50/20' : '' }}">
                                @if ($isCto)
                                    <td class="w-10 px-4 py-3.5 text-center align-middle">
                                        @if ($isDeletable)
                                            <input type="checkbox" 
                                                   value="{{ $user->id }}" 
                                                   x-model="selectedUserIds" 
                                                   @change="updateSelectAllUsersState()"
                                                   class="rounded-xs border-[#D5DEDF] text-[#167C78] focus:ring-[#167C78] cursor-pointer">
                                        @else
                                            <span class="text-[10px] text-gray-300" title="Protected account">&mdash;</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-6 py-3.5 align-middle">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-[#102A36] text-white flex items-center justify-center font-bold text-xs shrink-0 uppercase tracking-tighter mt-0.5">
                                            {{ substr($user->name, 0, 2) }}
                                        </div>
                                        <div class="space-y-0.5">
                                            <div class="font-bold text-[#102A36] text-xs flex items-center gap-1.5 flex-wrap">
                                                <span>{{ $user->name }}</span>
                                                @if ($user->isCto())
                                                    <span class="px-1.5 py-0.2 bg-amber-500 text-white text-[8px] font-bold rounded-xs uppercase">CTO</span>
                                                @elseif ($userRole?->is_admin)
                                                    <span class="px-1.5 py-0.2 bg-emerald-600 text-white text-[8px] font-bold rounded-xs uppercase">Admin</span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-[#61727D]">
                                                {{ $user->job_title ?? 'Platform User' }}
                                            </div>
                                            <!-- Sign-in email & phone under User & Job Title -->
                                            <div class="flex items-center gap-1 text-[11px] font-mono text-[#102A36] pt-0.5">
                                                <svg class="w-3 h-3 text-[#167C78] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                                </svg>
                                                <span>{{ $user->email }}</span>
                                                @if ($user->phone)
                                                    <span class="text-[#8A9BA8] font-sans">•</span>
                                                    <span class="text-[#61727D]">{{ $user->phone }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 align-middle">
                                    @if ($userOrg)
                                        <div class="space-y-0.5">
                                            <div class="font-bold text-[#102A36] text-xs flex items-center gap-1">
                                                <span>{{ $userOrg->name }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="px-1.5 py-0.2 text-[9px] font-bold uppercase rounded-xs tracking-wider
                                                    @if ($userOrg->type->value === 'hospital') bg-[#167C78]/10 text-[#167C78]
                                                    @elseif ($userOrg->type->value === 'laboratory') bg-[#102A36]/10 text-[#102A36]
                                                    @elseif ($userOrg->type->value === 'platform') bg-purple-100 text-purple-800
                                                    @else bg-[#94611A]/10 text-[#94611A] @endif">
                                                    {{ $userOrg->type->value }}
                                                </span>
                                                <span class="text-[10px] text-[#61727D]">• {{ $userOrg->city ?? 'Accra' }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded-xs border border-amber-200">
                                            Unassigned
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 align-middle">
                                    <div class="space-y-1.5">
                                        <div>
                                            @if ($userRole)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-xs text-[10px] font-bold tracking-tight
                                                    @if ($userRole->is_admin) bg-emerald-100 text-emerald-800 border border-emerald-300
                                                    @elseif (str_contains($userRole->key ?? '', 'pathologist') || str_contains($userRole->key ?? '', 'scientist')) bg-indigo-100 text-indigo-800 border border-indigo-200
                                                    @elseif (str_contains($userRole->key ?? '', 'clinician') || str_contains($userRole->key ?? '', 'doctor')) bg-sky-100 text-sky-800 border border-sky-200
                                                    @elseif (str_contains($userRole->key ?? '', 'finance')) bg-amber-100 text-amber-800 border border-amber-300
                                                    @else bg-slate-100 text-slate-800 border border-slate-300 @endif">
                                                    {{ $userRole->name }}
                                                </span>
                                            @else
                                                <span class="text-[10px] text-[#8A9BA8] italic">No active role</span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase
                                                @if ($user->status === 'active') bg-emerald-50 text-emerald-700 border border-emerald-200
                                                @elseif ($user->status === 'suspended') bg-amber-50 text-amber-700 border border-amber-200
                                                @else bg-gray-50 text-gray-600 border border-gray-200 @endif">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                                {{ ucfirst($user->status ?? 'active') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 text-right whitespace-nowrap align-middle">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        @if ($isCto)
                                            @if (auth()->id() !== $user->id)
                                                <form method="POST" action="{{ route('ops.impersonate.user', $user) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            title="Impersonate {{ $user->name }} ({{ $userRole?->name }})"
                                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-800 rounded-xs text-[10px] font-bold transition flex items-center gap-1 shadow-2xs">
                                                        <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                        </svg>
                                                        <span>Impersonate</span>
                                                    </button>
                                                </form>

                                                @if ($isDeletable)
                                                    <button type="button" 
                                                            @click="openDeleteUserModal({{ json_encode([
                                                                'id' => $user->id,
                                                                'uuid' => $user->uuid,
                                                                'name' => $user->name,
                                                                'email' => $user->email,
                                                                'job_title' => $user->job_title ?? 'Platform User',
                                                                'org_name' => $userOrg?->name ?? 'Unassigned'
                                                            ]) }})"
                                                            title="Delete {{ $user->name }}"
                                                            class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-700 hover:text-rose-900 rounded-xs text-[10px] font-bold transition flex items-center gap-1 shadow-2xs">
                                                        <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                        <span>Delete</span>
                                                    </button>
                                                @endif
                                            @else
                                                <span class="px-2 py-1 bg-gray-100 text-[#8A9BA8] border border-gray-200 rounded-xs text-[10px] font-medium italic">Active Session</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isCto ? 5 : 4 }}" class="px-6 py-12 text-center text-[#61727D]">
                                    No partner personnel found matching the query.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Confirmation / Status Modal -->
        <div x-show="confirmModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="confirmModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">
                        <span x-show="targetStatus === 'active'">Confirm & Activate Institution</span>
                        <span x-show="targetStatus === 'rejected'">Decline Access Request</span>
                        <span x-show="targetStatus === 'suspended'">Suspend Institutional Access</span>
                    </h3>
                    <button @click="confirmModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form :action="'/ops/partners/' + (selectedPartner ? (selectedPartner.uuid || selectedPartner.id) : '') + '/status'" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" :value="targetStatus">

                    <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] space-y-1">
                        <div class="text-[10px] text-[#61727D] uppercase font-bold">Selected Institution:</div>
                        <div class="font-bold text-[#102A36] text-sm" x-text="selectedPartner?.name"></div>
                        <div class="text-[11px] text-[#61727D]" x-text="'Category: ' + (selectedPartner?.type || '') + ' • ' + (selectedPartner?.city || '')"></div>
                    </div>

                    <div x-show="targetStatus === 'active'" class="p-3 bg-[#E8F0EF] border border-[#167C78]/30 text-[#167C78] space-y-1 text-[11px]">
                        <div class="font-bold flex items-center gap-1">
                            <span>✓</span>
                            <span>Immediate Activation Notice</span>
                        </div>
                        <p class="leading-relaxed">
                            Confirming this institution will immediately unlock their portal workspace and dispatch an automated activation email to the clinical lead.
                        </p>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">
                            Operational Notes / Regulatory Audit Reason
                        </label>
                        <textarea name="status_reason" x-model="statusReason" rows="2"
                                  placeholder="e.g. Verified against HEFRA/AHPC registry; license credentials valid through 2027..."
                                  class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none"></textarea>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-2">
                        <button type="button" @click="confirmModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] text-[#61727D] font-bold">
                            Cancel
                        </button>
                        <button type="submit" 
                                :class="targetStatus === 'active' ? 'bg-[#247451] hover:bg-[#1b593e]' : (targetStatus === 'rejected' ? 'bg-[#B42332] hover:bg-[#8f1c28]' : 'bg-[#94611A] hover:bg-[#784d14]')"
                                class="px-5 py-2 text-white font-bold uppercase tracking-wider transition">
                            <span x-text="targetStatus === 'active' ? 'Confirm & Activate' : (targetStatus === 'rejected' ? 'Decline Request' : 'Confirm Suspension')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Institution Modal (Single Institution at Verification Action) -->
        <div x-show="deleteModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="deleteModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-rose-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-rose-100 text-rose-700 rounded-xs">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Delete Institution &amp; Personnel</h3>
                    </div>
                    <button @click="deleteModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form :action="'/ops/partners/' + (selectedPartnerForDelete ? (selectedPartnerForDelete.uuid || selectedPartnerForDelete.id) : '')" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('DELETE')

                    <div class="p-3 bg-rose-50/60 border border-rose-200 space-y-1">
                        <div class="text-[10px] text-rose-700 uppercase font-bold">Institution to Delete:</div>
                        <div class="font-bold text-[#102A36] text-sm" x-text="selectedPartnerForDelete?.name"></div>
                        <div class="text-[11px] text-[#61727D]" x-text="'Category: ' + (selectedPartnerForDelete?.type || '') + ' • ' + (selectedPartnerForDelete?.city || '')"></div>
                    </div>

                    <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] text-[#61727D] space-y-1 text-[11px]">
                        <div class="font-bold text-[#102A36] flex items-center gap-1">
                            <span class="text-rose-600 font-bold">⚠</span>
                            <span>Chief Technical Officer Authorization Required</span>
                        </div>
                        <p class="leading-relaxed">
                            Deleting this institution will remove its network directory profile and clean up all associated team members, credentials, and pending invitations.
                        </p>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-2">
                        <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] text-[#61727D] font-bold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold uppercase tracking-wider transition shadow-sm">
                            Confirm Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete All / Bulk Delete Modal -->
        <div x-show="deleteAllModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="deleteAllModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-lg w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-rose-200 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-rose-100 text-rose-700 rounded-xs">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-rose-700 uppercase tracking-wider">
                            <span x-show="selectedIds.length > 0" x-text="'Delete Selected Institutions (' + selectedIds.length + ')'"></span>
                            <span x-show="selectedIds.length === 0">Delete All Registered Partner Institutions</span>
                        </h3>
                    </div>
                    <button @click="deleteAllModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form action="{{ route('ops.partners.destroyAll') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids" :value="selectedIds.length > 0 ? JSON.stringify(selectedIds) : ''">

                    <div class="p-3 bg-rose-50 border border-rose-200 text-rose-900 space-y-2">
                        <div class="font-bold flex items-center gap-1">
                            <span>⚠</span>
                            <span x-show="selectedIds.length > 0">Bulk Deletion Notice</span>
                            <span x-show="selectedIds.length === 0">Full Partner Purge Notice</span>
                        </div>
                        <p class="leading-relaxed text-[11px]" x-show="selectedIds.length > 0">
                            You are about to permanently delete <strong x-text="selectedIds.length"></strong> selected partner institution(s) and their associated team members. Core platform operations are safely preserved.
                        </p>
                        <p class="leading-relaxed text-[11px]" x-show="selectedIds.length === 0">
                            You are about to permanently delete <strong>ALL</strong> registered partner institutions (hospitals, laboratories, corporate clients) and their associated team member credentials from the platform. Core platform operations are safely preserved.
                        </p>
                    </div>

                    <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] text-[#61727D] space-y-1 text-[11px]">
                        <div class="font-bold text-[#102A36]">Authorized Chief Technical Officer Action</div>
                        <p class="leading-relaxed">
                            This operation is irreversible and will be permanently recorded in the SHA-256 cryptographic compliance audit log.
                        </p>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-2">
                        <button type="button" @click="deleteAllModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] text-[#61727D] font-bold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rose-700 hover:bg-rose-800 text-white font-bold uppercase tracking-wider transition shadow-sm">
                            <span x-show="selectedIds.length > 0">Confirm Delete Selected</span>
                            <span x-show="selectedIds.length === 0">Confirm Delete All Partners</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Remove Individual Team Member Modal -->
        <div x-show="deleteMemberModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="deleteMemberModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-rose-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-rose-100 text-rose-700 rounded-xs">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Remove Personnel</h3>
                    </div>
                    <button @click="deleteMemberModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form :action="'/ops/partners/' + (selectedPartnerForDelete?.uuid || selectedPartnerForDelete?.id) + '/members/' + selectedMemberForDelete?.id" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('DELETE')

                    <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] space-y-1">
                        <div class="text-[10px] text-[#61727D] uppercase font-bold">Team Member:</div>
                        <div class="font-bold text-[#102A36] text-sm" x-text="selectedMemberForDelete?.name"></div>
                        <div class="text-[11px] text-[#61727D] font-mono" x-text="selectedMemberForDelete?.email"></div>
                        <div class="text-[10px] text-[#167C78]" x-text="'Institution: ' + (selectedPartnerForDelete?.name || '')"></div>
                    </div>

                    <p class="text-[11px] text-[#61727D] leading-relaxed">
                        Are you sure you want to remove this team member? Their institutional membership will be revoked immediately.
                    </p>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-2">
                        <button type="button" @click="deleteMemberModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] text-[#61727D] font-bold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold uppercase tracking-wider transition shadow-sm">
                            Confirm Removal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Single User Delete Confirmation Modal -->
        <div x-show="deleteUserModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="deleteUserModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-rose-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-rose-100 text-rose-700 rounded-xs">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Delete Platform User</h3>
                    </div>
                    <button @click="deleteUserModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form :action="'/ops/users/' + (selectedUserForDelete ? (selectedUserForDelete.uuid || selectedUserForDelete.id) : '')" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('DELETE')

                    <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] space-y-1">
                        <div class="text-[10px] text-[#61727D] uppercase font-bold">User Account:</div>
                        <div class="font-bold text-[#102A36] text-sm" x-text="selectedUserForDelete?.name"></div>
                        <div class="text-[11px] text-[#61727D] font-mono" x-text="selectedUserForDelete?.email"></div>
                        <div class="text-[10px] text-[#167C78]" x-text="'Organization: ' + (selectedUserForDelete?.org_name || 'Unassigned')"></div>
                    </div>

                    <p class="text-[11px] text-[#61727D] leading-relaxed">
                        Are you sure you want to permanently delete this user account from the platform roster? Their institutional memberships and active sessions will be terminated immediately.
                    </p>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-2">
                        <button type="button" @click="deleteUserModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] text-[#61727D] font-bold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold uppercase tracking-wider transition shadow-sm">
                            Confirm Deletion
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete All / Selected Platform Users Modal -->
        <div x-show="deleteAllUsersModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="deleteAllUsersModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-rose-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-rose-100 text-rose-700 rounded-xs">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-rose-700 uppercase tracking-wider">
                            <span x-show="selectedUserIds.length > 0" x-text="'Delete Selected Users (' + selectedUserIds.length + ')'"></span>
                            <span x-show="selectedUserIds.length === 0">Delete All Registered Platform Users</span>
                        </h3>
                    </div>
                    <button @click="deleteAllUsersModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form action="{{ route('ops.users.destroyAll') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids" :value="selectedUserIds.length > 0 ? JSON.stringify(selectedUserIds) : ''">

                    <div class="p-3 bg-rose-50 border border-rose-200 text-rose-900 space-y-2">
                        <div class="font-bold flex items-center gap-1">
                            <span>⚠</span>
                            <span x-show="selectedUserIds.length > 0">Bulk User Deletion Notice</span>
                            <span x-show="selectedUserIds.length === 0">Full User Roster Purge Notice</span>
                        </div>
                        <p class="leading-relaxed text-[11px]" x-show="selectedUserIds.length > 0">
                            You are about to permanently delete <strong x-text="selectedUserIds.length"></strong> selected platform user account(s). Their organization memberships and active sessions will be terminated immediately.
                        </p>
                        <p class="leading-relaxed text-[11px]" x-show="selectedUserIds.length === 0">
                            You are about to permanently delete <strong>ALL</strong> registered platform users from the platform roster. Your Chief Technical Officer account and active session will be strictly preserved.
                        </p>
                    </div>

                    <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] text-[#61727D] space-y-1 text-[11px]">
                        <div class="font-bold text-[#102A36]">Authorized Chief Technical Officer Action</div>
                        <p class="leading-relaxed">
                            This operation is irreversible and will be permanently recorded in the SHA-256 cryptographic compliance audit log.
                        </p>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-2">
                        <button type="button" @click="deleteAllUsersModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] text-[#61727D] font-bold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rose-700 hover:bg-rose-800 text-white font-bold uppercase tracking-wider transition shadow-sm">
                            <span x-show="selectedUserIds.length > 0">Confirm Bulk Deletion</span>
                            <span x-show="selectedUserIds.length === 0">Confirm Delete All Users</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function() {
        function registerPartnersDirectory() {
            if (!window.Alpine) return;
            window.Alpine.data('partnersDirectory', (nonPlatformIds, allPagePartnerIds, deletableUserIds, initialQuery, initialType, initialStatus) => ({
                activeTab: 'organizations',
                confirmModalOpen: false,
                deleteModalOpen: false,
                deleteAllModalOpen: false,
                deleteMemberModalOpen: false,
                deleteUserModalOpen: false,
                deleteAllUsersModalOpen: false,
                selectedPartner: null,
                selectedPartnerForDelete: null,
                selectedMemberForDelete: null,
                selectedUserForDelete: null,
                targetStatus: 'active',
                statusReason: '',
                selectedIds: [],
                allDeletableIds: nonPlatformIds || [],
                allOrgIds: allPagePartnerIds || [],
                selectedUserIds: [],
                allDeletableUserIds: deletableUserIds || [],
                selectAllUsers: false,
                expandedOrgs: [],
                selectAll: false,

                searchQuery: initialQuery || '',
                selectedType: initialType || '',
                currentStatus: initialStatus || '',
                isLoading: false,
                searchDebounceTimer: null,

                triggerLiveSearch() {
                    clearTimeout(this.searchDebounceTimer);
                    this.searchDebounceTimer = setTimeout(() => {
                        this.performSearch();
                    }, 250);
                },

                async performSearch() {
                    const params = new URLSearchParams();
                    if (this.searchQuery && this.searchQuery.trim() !== '') {
                        params.set('q', this.searchQuery.trim());
                    }
                    if (this.selectedType) {
                        params.set('type', this.selectedType);
                    }
                    if (this.currentStatus) {
                        params.set('status', this.currentStatus);
                    }

                    const url = '{{ route('ops.partners.index') }}' + (params.toString() ? '?' + params.toString() : '');
                    await this.fetchAndSwap(url);
                },

                filterByStatus(status) {
                    this.currentStatus = status;
                    this.performSearch();
                },

                clearSearch() {
                    this.searchQuery = '';
                    this.performSearch();
                },

                clearAllFilters() {
                    this.searchQuery = '';
                    this.selectedType = '';
                    this.currentStatus = '';
                    this.performSearch();
                },

                async fetchAndSwap(url) {
                    this.isLoading = true;
                    try {
                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (response.ok) {
                            const html = await response.text();
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');

                            // 1. Update Organizations list container
                            const newOrg = doc.getElementById('organizations-container');
                            const curOrg = document.getElementById('organizations-container');
                            if (newOrg && curOrg) {
                                curOrg.innerHTML = newOrg.innerHTML;
                                if (window.Alpine) {
                                    window.Alpine.initTree(curOrg);
                                }
                                const dataEl = curOrg.querySelector('#deletable-ids-data');
                                if (dataEl) {
                                    try {
                                        this.allDeletableIds = JSON.parse(dataEl.getAttribute('data-non-platform-ids') || '[]');
                                        this.allOrgIds = JSON.parse(dataEl.getAttribute('data-all-org-ids') || '[]');
                                        this.selectedIds = [];
                                        this.selectAll = false;
                                    } catch (e) {}
                                }
                            }

                            // 2. Update Users directory container
                            const newUsers = doc.getElementById('users-container');
                            const curUsers = document.getElementById('users-container');
                            if (newUsers && curUsers) {
                                curUsers.innerHTML = newUsers.innerHTML;
                                if (window.Alpine) {
                                    window.Alpine.initTree(curUsers);
                                }
                                const userDataEl = curUsers.querySelector('#deletable-user-ids-data');
                                if (userDataEl) {
                                    try {
                                        this.allDeletableUserIds = JSON.parse(userDataEl.getAttribute('data-deletable-user-ids') || '[]');
                                        this.selectedUserIds = [];
                                        this.selectAllUsers = false;
                                    } catch (e) {}
                                }
                            }

                            // 3. Update Status filter tabs
                            const newStatus = doc.getElementById('status-filter-tabs');
                            const curStatus = document.getElementById('status-filter-tabs');
                            if (newStatus && curStatus) {
                                curStatus.innerHTML = newStatus.innerHTML;
                                if (window.Alpine) {
                                    window.Alpine.initTree(curStatus);
                                }
                            }

                            // 4. Update tab switcher labels/counts
                            const newTabs = doc.getElementById('view-switcher-tabs');
                            const curTabs = document.getElementById('view-switcher-tabs');
                            if (newTabs && curTabs) {
                                curTabs.innerHTML = newTabs.innerHTML;
                                if (window.Alpine) {
                                    window.Alpine.initTree(curTabs);
                                }
                            }

                            // 5. Update Quick Metrics Grid
                            const newMetrics = doc.getElementById('quick-metrics-container');
                            const curMetrics = document.getElementById('quick-metrics-container');
                            if (newMetrics && curMetrics) {
                                curMetrics.innerHTML = newMetrics.innerHTML;
                            }

                            // 6. Update address bar
                            window.history.replaceState({}, '', url);
                        }
                    } catch (err) {
                        console.error('Error during live search:', err);
                    } finally {
                        this.isLoading = false;
                    }
                },

                init() {
                    // Handle pagination clicks within organizations container
                    document.addEventListener('click', (e) => {
                        const link = e.target.closest('#organizations-container a');
                        if (link && link.href && (link.closest('nav[role="navigation"]') || link.closest('.pagination'))) {
                            e.preventDefault();
                            this.fetchAndSwap(link.href);
                        }
                    });
                },

                toggleSelectAll() {
                    if (this.selectAll) {
                        this.selectedIds = [...this.allDeletableIds];
                    } else {
                        this.selectedIds = [];
                    }
                },
                updateSelectAllState() {
                    this.selectAll = this.allDeletableIds.length > 0 && this.selectedIds.length === this.allDeletableIds.length;
                },
                toggleOrg(id) {
                    if (this.expandedOrgs.includes(id)) {
                        this.expandedOrgs = this.expandedOrgs.filter(item => item !== id);
                    } else {
                        this.expandedOrgs.push(id);
                    }
                },
                toggleAllOrgs() {
                    if (this.expandedOrgs.length === this.allOrgIds.length) {
                        this.expandedOrgs = [];
                    } else {
                        this.expandedOrgs = [...this.allOrgIds];
                    }
                },
                openStatusModal(partner, status) {
                    this.selectedPartner = partner;
                    this.targetStatus = status;
                    this.statusReason = '';
                    this.confirmModalOpen = true;
                },
                openDeleteModal(partner) {
                    this.selectedPartnerForDelete = partner;
                    this.deleteModalOpen = true;
                },
                openDeleteAllModal() {
                    this.deleteAllModalOpen = true;
                },
                openDeleteMemberModal(partner, memberUser, membershipId) {
                    this.selectedPartnerForDelete = partner;
                    this.selectedMemberForDelete = {
                        id: membershipId,
                        name: memberUser.name,
                        email: memberUser.email,
                        job_title: memberUser.job_title
                    };
                    this.deleteMemberModalOpen = true;
                },
                openDeleteUserModal(user) {
                    this.selectedUserForDelete = user;
                    this.deleteUserModalOpen = true;
                },
                openDeleteAllUsersModal() {
                    this.deleteAllUsersModalOpen = true;
                },
                toggleSelectAllUsers() {
                    if (this.selectAllUsers) {
                        this.selectedUserIds = [...this.allDeletableUserIds];
                    } else {
                        this.selectedUserIds = [];
                    }
                },
                updateSelectAllUsersState() {
                    this.selectAllUsers = this.allDeletableUserIds.length > 0 && this.selectedUserIds.length === this.allDeletableUserIds.length;
                }
            }));
        }

        if (window.Alpine) {
            registerPartnersDirectory();
        } else {
            document.addEventListener('alpine:init', registerPartnersDirectory);
        }
    })();
    </script>
</x-layouts.workspace>