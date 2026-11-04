<x-layouts.workspace title="Hospital Team & Access Governance — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-hospital-sidebar active="team" />
    </x-slot:sidebar>

    <div x-data="{
        inviteModalOpen: false,
        editModalOpen: false,
        selectedMember: null,
        provisionMode: 'create_direct',
        selectedRoleId: '{{ $roles->where('key', 'hospital_clinician')->first()?->id ?? $roles->first()?->id }}',
        editRoleId: '',
        editStatus: 'active'
    }" class="space-y-6">

        <!-- Top Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#102A36]">Hospital Team & Access Governance</h1>
                <p class="text-xs text-[#61727D]">
                    Clinical personnel provisioning, diagnostic referral delegation, and role-based access control.
                </p>
            </div>
            <button @click="inviteModalOpen = true" 
                    class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white text-xs font-bold uppercase tracking-wider transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                + Provision / Invite Staff
            </button>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-sm flex items-start gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded-sm flex items-start gap-2">
                <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Active Personnel</div>
                <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $members->where('status', 'active')->count() }}</div>
                <div class="text-[10px] text-[#61727D] mt-1">Provisioned hospital operators</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Clinicians & Physicians</div>
                <div class="text-2xl font-bold text-[#167C78] mt-1">
                    {{ $members->where('role.key', 'hospital_clinician')->count() }}
                </div>
                <div class="text-[10px] text-[#61727D] mt-1">Referral creation & result review</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Phlebotomy & Logistics</div>
                <div class="text-2xl font-bold text-[#94611A] mt-1">
                    {{ $members->where('role.key', 'hospital_coordinator')->count() }}
                </div>
                <div class="text-[10px] text-[#61727D] mt-1">Specimen custody & courier handover</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF]">
                <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Invoicing & Finance</div>
                <div class="text-2xl font-bold text-[#247451] mt-1">
                    {{ $members->where('role.key', 'hospital_finance')->count() }}
                </div>
                <div class="text-[10px] text-[#61727D] mt-1">Statements & payments</div>
            </div>
        </div>

        <!-- Role Protocols Reference Banner -->
        <div class="bg-[#F5F3ED] border border-[#D5DEDF] p-4 text-xs space-y-3">
            <div class="font-bold text-[#102A36] uppercase tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#167C78]"></span>
                Hospital Institutional Role Protocols & Segregated Scopes
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-[11px]">
                <div class="p-3 bg-white border border-[#D5DEDF] rounded-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <strong class="text-[#102A36]">1. Clinician / Physician</strong>
                        <span class="px-2 py-0.5 bg-[#167C78]/10 text-[#167C78] font-bold text-[9px] uppercase rounded-sm">Clinical</span>
                    </div>
                    <p class="text-[#61727D]">
                        Registers patients, creates diagnostic referral orders, orders laboratory investigations, and reviews official clinical results.
                    </p>
                </div>
                <div class="p-3 bg-white border border-[#D5DEDF] rounded-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <strong class="text-[#102A36]">2. Referral Coordinator</strong>
                        <span class="px-2 py-0.5 bg-[#94611A]/10 text-[#94611A] font-bold text-[9px] uppercase rounded-sm">Logistics</span>
                    </div>
                    <p class="text-[#61727D]">
                        Performs phlebotomy, records specimen collection, and executes courier dispatches. Segregated from accessing patient clinical results.
                    </p>
                </div>
                <div class="p-3 bg-white border border-[#D5DEDF] rounded-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <strong class="text-[#102A36]">3. Finance Officer</strong>
                        <span class="px-2 py-0.5 bg-[#247451]/10 text-[#247451] font-bold text-[9px] uppercase rounded-sm">Finance</span>
                    </div>
                    <p class="text-[#61727D]">
                        Audits diagnostic settlement invoices, executes bank transfer settlement payments, and reviews institutional balance statements.
                    </p>
                </div>
            </div>
        </div>

        <!-- Active Hospital Personnel Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="p-4 border-b border-[#D5DEDF] flex justify-between items-center bg-[#F5F3ED]/40">
                <h2 class="text-xs font-bold uppercase tracking-wider text-[#102A36]">
                    Active Hospital Personnel
                </h2>
                <span class="text-xs text-[#61727D]">{{ $members->count() }} member(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Team Member</th>
                            <th class="px-6 py-3">Assigned Role & Protocol</th>
                            <th class="px-6 py-3">Delegated Capabilities</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($members as $m)
                            <tr class="hover:bg-[#E8F0EF]/30 transition">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-[#102A36] text-white flex items-center justify-center font-bold text-xs uppercase">
                                            {{ substr($m->user->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-[#102A36] flex items-center gap-1.5">
                                                {{ $m->user->name }}
                                                @if ($m->user_id === auth()->id())
                                                    <span class="px-1.5 py-0.2 bg-[#167C78] text-white text-[9px] font-bold uppercase rounded-sm">You</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-[#61727D] font-mono">{{ $m->user->email }}</div>
                                            <div class="text-[10px] text-[#61727D] italic">{{ $m->user->job_title }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5">
                                    @php
                                        $protocol = $protocols[$m->role?->key] ?? null;
                                    @endphp
                                    <div class="space-y-0.5">
                                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                            @if ($m->role?->key === 'hospital_admin') bg-indigo-50 border border-indigo-200 text-indigo-800
                                            @elseif ($m->role?->key === 'hospital_clinician') bg-teal-50 border border-teal-200 text-teal-800
                                            @elseif ($m->role?->key === 'hospital_coordinator') bg-amber-50 border border-amber-200 text-amber-800
                                            @else bg-green-50 border border-green-200 text-green-800 @endif">
                                            {{ $m->role?->name ?? 'Personnel' }}
                                        </span>
                                        @if ($protocol)
                                            <div class="text-[10px] text-[#61727D] font-medium">
                                                {{ $protocol['name'] }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @if ($m->role?->is_admin)
                                            <span class="px-2 py-0.5 bg-[#102A36] text-white text-[9px] font-bold uppercase rounded-sm">Full Hospital Admin</span>
                                        @elseif ($m->role?->key === 'hospital_clinician')
                                            <span class="px-2 py-0.5 bg-[#167C78]/10 text-[#167C78] text-[9px] font-bold uppercase rounded-sm font-mono">orders.create</span>
                                            <span class="px-2 py-0.5 bg-[#167C78]/10 text-[#167C78] text-[9px] font-bold uppercase rounded-sm font-mono">results.view</span>
                                        @elseif ($m->role?->key === 'hospital_coordinator')
                                            <span class="px-2 py-0.5 bg-[#94611A]/10 text-[#94611A] text-[9px] font-bold uppercase rounded-sm font-mono">specimens.dispatch</span>
                                            <span class="px-2 py-0.5 bg-gray-100 text-[#61727D] text-[9px] font-mono">orders.view</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-[#247451]/10 text-[#247451] text-[9px] font-bold uppercase rounded-sm font-mono">finance.pay</span>
                                            <span class="px-2 py-0.5 bg-gray-100 text-[#61727D] text-[9px] font-mono">finance.view</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm
                                        {{ $m->status === 'active' ? 'bg-[#247451]/10 text-[#247451]' : 'bg-[#B42332]/10 text-[#B42332]' }}">
                                        {{ $m->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right font-medium">
                                    <button @click="selectedMember = {{ json_encode($m) }}; editRoleId = '{{ $m->role_id }}'; editStatus = '{{ $m->status }}'; editModalOpen = true"
                                            class="text-[#167C78] hover:underline font-bold text-xs">
                                        Edit Role & Status
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-[#61727D]">
                                    No personnel registered yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Invitations Table -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="p-4 border-b border-[#D5DEDF] flex justify-between items-center bg-[#F5F3ED]/40">
                <h2 class="text-xs font-bold uppercase tracking-wider text-[#102A36]">
                    Pending Staff Invitations
                </h2>
                <span class="text-xs text-[#61727D]">{{ $invitations->count() }} pending</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Invitee</th>
                            <th class="px-6 py-3">Assigned Role</th>
                            <th class="px-6 py-3">Invited By & Sent</th>
                            <th class="px-6 py-3">Status / Expiry</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($invitations as $inv)
                            <tr class="hover:bg-[#E8F0EF]/30 transition">
                                <td class="px-6 py-3.5">
                                    <div class="font-bold text-[#102A36]">{{ $inv->name ?? 'Invited Personnel' }}</div>
                                    <div class="text-[11px] text-[#61727D] font-mono">{{ $inv->email }}</div>
                                    <div class="text-[10px] text-[#61727D]">{{ $inv->job_title }}</div>
                                </td>
                                <td class="px-6 py-3.5 font-bold uppercase text-[11px] text-[#167C78]">
                                    {{ $inv->role?->name }}
                                </td>
                                <td class="px-6 py-3.5 text-[#61727D]">
                                    {{ $inv->inviter?->name ?? 'Admin' }}<br>
                                    <span class="text-[10px]">{{ $inv->created_at->format('M d, Y H:i') }}</span>
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 bg-amber-50 text-amber-800 text-[10px] font-bold uppercase rounded-sm border border-amber-200">
                                        {{ $inv->status }}
                                    </span>
                                    <div class="text-[10px] text-[#61727D] mt-0.5">Expires {{ $inv->expires_at?->diffForHumans() }}</div>
                                </td>
                                <td class="px-6 py-3.5 text-right space-x-2">
                                    <form action="{{ route('hospital.team.invitations.resend', $inv) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-[#167C78] hover:underline font-bold text-xs">
                                            Resend
                                        </button>
                                    </form>
                                    <form action="{{ route('hospital.team.invitations.revoke', $inv) }}" method="POST" class="inline" onsubmit="return confirm('Revoke this invitation?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline font-bold text-xs">
                                            Revoke
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-6 text-center text-[#61727D]">
                                    No pending invitations.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Provision / Invite Staff Modal -->
        <div x-show="inviteModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="inviteModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-lg w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Provision Hospital Personnel</h3>
                    <button @click="inviteModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form action="{{ route('hospital.team.invite') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <!-- Provisioning Mode Switch -->
                    <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF] space-y-2">
                        <label class="block font-bold text-[#102A36] uppercase tracking-wider text-[11px]">Provisioning Mode</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2 border bg-white cursor-pointer"
                                   :class="provisionMode === 'create_direct' ? 'border-[#167C78] text-[#167C78] font-bold' : 'border-[#D5DEDF] text-[#61727D]'">
                                <input type="radio" name="mode" value="create_direct" x-model="provisionMode" class="text-[#167C78]">
                                <span>Immediate Provisioning</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border bg-white cursor-pointer"
                                   :class="provisionMode === 'invite_email' ? 'border-[#167C78] text-[#167C78] font-bold' : 'border-[#D5DEDF] text-[#61727D]'">
                                <input type="radio" name="mode" value="invite_email" x-model="provisionMode" class="text-[#167C78]">
                                <span>Email Invitation</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required placeholder="e.g. Dr. Ama Serwaa"
                                   class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Institutional Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" required placeholder="e.g. clinician@hospital.com"
                                   class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Professional Job Title <span class="text-red-500">*</span></label>
                        <input type="text" name="job_title" required placeholder="e.g. Senior Consultant Physician"
                               class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                    </div>

                    <!-- Role Protocol Selection -->
                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">Assigned Hospital Role & Protocol <span class="text-red-500">*</span></label>
                        <select name="role_id" x-model="selectedRoleId" required
                                class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none font-semibold">
                            @foreach ($roles as $r)
                                <option value="{{ $r->id }}">
                                    {{ $r->name }} ({{ $protocols[$r->key]['name'] ?? 'Standard' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Password field if direct mode -->
                    <div x-show="provisionMode === 'create_direct'">
                        <label class="block font-bold text-[#102A36] mb-1">Initial Temporary Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" ::required="provisionMode === 'create_direct'" placeholder="Minimum 8 characters"
                               class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                        <div class="text-[10px] text-[#61727D] mt-1">The user will be able to log in immediately with this password.</div>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="inviteModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                            Save & Provision
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Role & Status Modal -->
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="editModalOpen = false" class="bg-white border border-[#D5DEDF] max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <h3 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Edit Personnel Protocol</h3>
                    <button @click="editModalOpen = false" class="text-gray-400 hover:text-black font-bold">&times;</button>
                </div>

                <template x-if="selectedMember">
                    <form :action="'/hospital/team/' + selectedMember.id" method="POST" class="space-y-4 text-xs">
                        @csrf
                        @method('PATCH')

                        <div class="p-3 bg-[#F5F3ED] border border-[#D5DEDF]">
                            <div class="font-bold text-[#102A36]" x-text="selectedMember.user.name"></div>
                            <div class="text-[#61727D] font-mono text-[11px]" x-text="selectedMember.user.email"></div>
                        </div>

                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Assigned Protocol & Role</label>
                            <select name="role_id" x-model="editRoleId" required
                                    class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                                @foreach ($roles as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-[#102A36] mb-1">Account Status</label>
                            <select name="status" x-model="editStatus" required
                                    class="w-full border border-[#D5DEDF] p-2 focus:border-[#167C78] focus:outline-none">
                                <option value="active">Active (Access Granted)</option>
                                <option value="suspended">Suspended (Access Revoked)</option>
                            </select>
                        </div>

                        <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 border border-[#D5DEDF] font-bold text-[#61727D]">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider">
                                Update Personnel
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

    </div>
</x-layouts.workspace>
