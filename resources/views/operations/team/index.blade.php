<x-layouts.workspace title="Operations Team & Access Governance — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="team" />
    </x-slot:sidebar>

    <div class="space-y-6" x-data="teamGovernance()">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-[#D5DEDF] pb-5">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider bg-[#E8F0EF] px-2.5 py-0.5 rounded-sm">
                        Central Governance
                    </span>
                    <span class="text-[11px] text-[#61727D] font-mono">Act 843 Enforced</span>
                </div>
                <h1 class="text-2xl font-bold text-[#102A36] mt-1">Operations Team & Access Governance</h1>
                <p class="text-xs text-[#61727D] mt-0.5">
                    Authorize personnel, assign operational roles, and configure granular sidebar menu access across the NODIVA network.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5">
                <button type="button" 
                        @click="openCreateRoleModal()"
                        class="px-3.5 py-2.5 bg-white hover:bg-[#F5F3ED] text-[#102A36] border border-[#D5DEDF] text-xs font-bold uppercase tracking-wider transition rounded-sm shadow-xs flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span>Define Platform Role</span>
                </button>

                <button type="button" 
                        @click="inviteModalOpen = true"
                        class="px-4 py-2.5 bg-[#167C78] hover:bg-[#126461] text-white text-xs font-bold uppercase tracking-wider transition rounded-sm shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>Invite Team Member</span>
                </button>
            </div>
        </div>

        <!-- Copy Feedback Banner -->
        <div x-show="copyMessage" x-cloak style="display: none;" 
             class="p-3 bg-[#247451]/10 border border-[#247451]/30 text-xs text-[#247451] font-semibold flex items-center gap-2 rounded-sm">
            <span>✓</span>
            <span x-text="copyMessage"></span>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white border border-[#D5DEDF] p-4 rounded-sm">
                <div class="text-[10px] font-bold uppercase tracking-wider text-[#61727D]">Active Personnel</div>
                <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $members->where('status', 'active')->count() }}</div>
                <div class="text-[11px] text-[#167C78] mt-0.5 font-medium">Provisioned console operators</div>
            </div>

            <div class="bg-white border border-[#D5DEDF] p-4 rounded-sm">
                <div class="text-[10px] font-bold uppercase tracking-wider text-[#61727D]">Pending Invitations</div>
                <div class="text-2xl font-bold text-[#94611A] mt-1">{{ $invitations->where('status', 'pending')->count() }}</div>
                <div class="text-[11px] text-[#61727D] mt-0.5">Awaiting invitee acceptance</div>
            </div>

            <div class="bg-white border border-[#D5DEDF] p-4 rounded-sm">
                <div class="text-[10px] font-bold uppercase tracking-wider text-[#61727D]">Platform Roles</div>
                <div class="text-2xl font-bold text-[#102A36] mt-1" x-text="roles.length"></div>
                <div class="text-[11px] text-[#61727D] mt-0.5">Role archetypes defined</div>
            </div>

            <div class="bg-white border border-[#D5DEDF] p-4 rounded-sm">
                <div class="text-[10px] font-bold uppercase tracking-wider text-[#61727D]">Access Segregation</div>
                <div class="text-2xl font-bold text-[#167C78] mt-1">Dual-Control</div>
                <div class="text-[11px] text-[#61727D] mt-0.5">No routine clinical access</div>
            </div>
        </div>

        <!-- Section 1: Active Team Members Table -->
        <div class="bg-white border border-[#D5DEDF] rounded-sm overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[#D5DEDF] bg-[#F5F3ED] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Active Operations Personnel</h2>
                    <p class="text-xs text-[#61727D]">Members with active credentials and delegated sidebar modules.</p>
                </div>
                <span class="text-xs font-mono font-bold text-[#167C78]">{{ $members->count() }} member(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-[#102A36] text-white uppercase text-[10px] tracking-wider">
                            <th class="px-6 py-3">Team Member</th>
                            <th class="px-6 py-3">Role & Title</th>
                            <th class="px-6 py-3">Authorized Sidebar Menus</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($members as $member)
                            <tr class="hover:bg-[#E8F0EF]/30 transition">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-[#102A36] text-white flex items-center justify-center font-bold text-xs uppercase flex-shrink-0">
                                            {{ substr($member->user->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-[#102A36] text-sm flex items-center gap-1.5">
                                                <span>{{ $member->user->name }}</span>
                                                @if ($member->user->isCto())
                                                    <span class="text-[9px] bg-[#102A36] text-[#E8F0EF] border border-[#167C78]/50 px-1.5 py-0.5 rounded-xs uppercase font-bold tracking-wider flex items-center gap-1">
                                                        <svg class="w-3 h-3 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                        </svg>
                                                        Root CTO
                                                    </span>
                                                @endif
                                                @if ($member->user_id === auth()->id())
                                                    <span class="text-[9px] bg-[#167C78] text-white px-1.5 py-0.2 rounded-xs uppercase font-bold">You</span>
                                                @endif
                                            </div>
                                            <div class="font-mono text-[11px] text-[#61727D]">{{ $member->user->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-3.5">
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm border
                                        {{ $member->role?->is_admin ? 'bg-[#102A36]/10 text-[#102A36] border-[#102A36]/30' : 'bg-[#167C78]/10 text-[#167C78] border-[#167C78]/30' }}">
                                        {{ $member->role?->name ?? 'Platform Member' }}
                                    </span>
                                    <div class="text-[11px] text-[#61727D] mt-0.5">
                                        {{ $member->user->job_title ?? 'Operations Staff' }}
                                    </div>
                                </td>

                                <td class="px-6 py-3.5 max-w-xs">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($member->role?->is_admin)
                                            <span class="px-1.5 py-0.5 bg-[#102A36] text-white rounded-xs text-[10px] font-semibold">
                                                All Menus (Full Admin)
                                            </span>
                                        @else
                                            @php
                                                $visibleCount = 0;
                                            @endphp
                                            @foreach ($sidebarPermissions as $key => $perm)
                                                @if ($member->can($key))
                                                    @php $visibleCount++; @endphp
                                                    <span class="px-1.5 py-0.5 bg-[#E8F0EF] text-[#167C78] border border-[#167C78]/30 rounded-xs text-[10px] font-medium"
                                                          title="{{ $perm['description'] }}">
                                                        {{ $perm['name'] }}
                                                    </span>
                                                @endif
                                            @endforeach
                                            @if ($visibleCount === 0)
                                                <span class="text-[10px] text-[#94611A] italic">No menus assigned</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-3.5 text-center">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm border
                                        {{ $member->status === 'active' ? 'bg-[#247451]/10 text-[#247451] border-[#247451]/30' : 'bg-[#B42332]/10 text-[#B42332] border-[#B42332]/30' }}">
                                        {{ $member->status }}
                                    </span>
                                </td>

                                <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        @if ($member->user->isCto() && $member->user_id !== auth()->id())
                                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#61727D] px-2.5 py-1 bg-[#F5F3ED] border border-[#D5DEDF] rounded-xs cursor-not-allowed select-none" 
                                                  title="The Chief Technical Officer role is root-protected and cannot be edited by any Platform Administrator.">
                                                <svg class="w-3.5 h-3.5 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                </svg>
                                                <span>Root Protected</span>
                                            </span>
                                        @else
                                            <!-- Action 1: Edit Menus & Role (Icon) -->
                                            <button type="button" 
                                                    @click="openEditModal({{ json_encode($member) }})"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-[#167C78] bg-[#E8F0EF] hover:bg-[#167C78] hover:text-white border border-[#167C78]/30 rounded-xs transition cursor-pointer"
                                                    title="Configure authorized sidebar menus, roles, and account state">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                <span>Edit</span>
                                            </button>

                                            <!-- Action 2: Impersonate Team Member (Icon, CTO privilege) -->
                                            @if (auth()->user()->isCto() && $member->user_id !== auth()->id() && ! $member->user->isCto())
                                                <form action="{{ route('ops.impersonate.user', $member->user) }}?return_to={{ urlencode(request()->fullUrl()) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('Start impersonation session as {{ addslashes($member->user->name) }} ({{ addslashes($member->user->job_title ?? 'Operations Staff') }})? You will view the Operations Console under their specific delegated permissions.');">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-[#102A36] bg-[#F5F3ED] hover:bg-[#102A36] hover:text-white border border-[#D5DEDF] rounded-xs transition cursor-pointer"
                                                            title="Impersonate {{ $member->user->name }} to audit their specific console view">
                                                        <svg class="w-3.5 h-3.5 text-[#167C78]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                        </svg>
                                                        <span>Impersonate</span>
                                                    </button>
                                                </form>
                                            @endif

                                            <!-- Action 3: Remove Member (Icon, CTO/Admin privilege) -->
                                            @if ((auth()->user()->isCto() || auth()->user()->isPlatformAdmin()) && $member->user_id !== auth()->id() && ! $member->user->isCto())
                                                <form action="{{ route('ops.team.destroy', $member) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('Are you sure you want to permanently remove {{ addslashes($member->user->name) }} ({{ addslashes($member->user->email) }}) from the Operations Console? Their credentials and console access will be revoked immediately.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-[#B42332] bg-red-50 hover:bg-[#B42332] hover:text-white border border-red-200 rounded-xs transition cursor-pointer"
                                                            title="Revoke console credentials and remove this team member">
                                                        <svg class="w-3.5 h-3.5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                        <span>Remove</span>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-[#61727D]">
                                    No active team members registered.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Pending Invitations Table -->
        <div class="bg-white border border-[#D5DEDF] rounded-sm overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[#D5DEDF] bg-[#F5F3ED] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Pending Access Invitations</h2>
                    <p class="text-xs text-[#61727D]">Dispatched invitations awaiting user acceptance and password setup.</p>
                </div>
                <span class="text-xs font-mono font-bold text-[#94611A]">{{ $invitations->where('status', 'pending')->count() }} pending</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-[#F5F3ED] text-[#61727D] uppercase text-[10px] tracking-wider border-b border-[#D5DEDF]">
                            <th class="px-6 py-3">Invitee</th>
                            <th class="px-6 py-3">Assigned Role</th>
                            <th class="px-6 py-3">Invited By & Sent</th>
                            <th class="px-6 py-3">Status / Expiry</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5DEDF]">
                        @forelse ($invitations as $invitation)
                            <tr class="hover:bg-[#E8F0EF]/30 transition {{ $invitation->status !== 'pending' ? 'opacity-60 bg-gray-50' : '' }}">
                                <td class="px-6 py-3.5">
                                    <div class="font-bold text-[#102A36] text-sm">{{ $invitation->name ?: 'New Operator' }}</div>
                                    <div class="font-mono text-[11px] text-[#167C78]">{{ $invitation->email }}</div>
                                    @if ($invitation->job_title)
                                        <div class="text-[10px] text-[#61727D]">{{ $invitation->job_title }}</div>
                                    @endif
                                </td>

                                <td class="px-6 py-3.5">
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/30">
                                        {{ $invitation->role->name ?? 'Operations' }}
                                    </span>
                                </td>

                                <td class="px-6 py-3.5">
                                    <div class="text-[#102A36] font-medium">{{ $invitation->inviter->name ?? 'Chief Operations Officer' }}</div>
                                    <div class="text-[10px] text-[#61727D]">{{ $invitation->created_at->diffForHumans() }}</div>
                                </td>

                                <td class="px-6 py-3.5">
                                    @if ($invitation->status === 'pending')
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#94611A]/10 text-[#94611A] border border-[#94611A]/30">
                                                Pending Acceptance
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-[#61727D] mt-0.5">Expires {{ $invitation->expires_at->diffForHumans() }}</div>
                                    @elseif ($invitation->status === 'accepted')
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#247451]/10 text-[#247451] border border-[#247451]/30">
                                            Accepted
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-[#B42332]/10 text-[#B42332] border border-[#B42332]/30">
                                            {{ ucfirst($invitation->status) }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        @if ($invitation->status === 'pending')
                                            <button type="button" 
                                                    @click="copyInviteLink('{{ route('invitations.accept', ['token' => $invitation->token]) }}')"
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold text-[#167C78] bg-[#E8F0EF] hover:bg-[#167C78] hover:text-white border border-[#167C78]/30 rounded-xs transition cursor-pointer"
                                                    title="Copy link to clipboard">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                                <span>Copy Link</span>
                                            </button>

                                            <form method="POST" action="{{ route('ops.team.invitations.resend', $invitation) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold text-[#102A36] bg-[#F5F3ED] hover:bg-[#102A36] hover:text-white border border-[#D5DEDF] rounded-xs transition cursor-pointer"
                                                        title="Resend invitation email">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                    </svg>
                                                    <span>Resend</span>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('ops.team.invitations.revoke', $invitation) }}" class="inline"
                                                  onsubmit="return confirm('Revoke this access invitation?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold text-[#B42332] bg-red-50 hover:bg-[#B42332] hover:text-white border border-red-200 rounded-xs transition cursor-pointer"
                                                        title="Revoke this invitation">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span>Revoke</span>
                                                </button>
                                            </form>
                                        @else
                                            @if (auth()->user()->isCto() || auth()->user()->isPlatformAdmin())
                                                <form method="POST" action="{{ route('ops.team.invitations.revoke', $invitation) }}" class="inline"
                                                      onsubmit="return confirm('Remove this invitation record from history?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold text-[#61727D] hover:text-[#B42332] hover:bg-red-50 border border-transparent hover:border-red-200 rounded-xs transition cursor-pointer"
                                                            title="Clear this record">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                        <span>Clear</span>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-[11px] text-[#61727D]">—</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-6 text-center text-[#61727D]">
                                    No pending invitations. All invitees have accepted or none have been sent.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Classic Modal: Invite Team Member -->
        <div x-show="inviteModalOpen" x-cloak style="display: none;" 
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="inviteModalOpen = false" 
                 class="bg-white border border-[#D5DEDF] max-w-2xl w-full p-6 space-y-5 shadow-2xl max-h-[92vh] overflow-y-auto rounded-sm">
                
                <!-- Modal Header -->
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#167C78]">Delegated Operations Access</span>
                        <h3 class="text-base font-bold text-[#102A36]">Invite Operations Team Member</h3>
                    </div>
                    <button type="button" @click="inviteModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form method="POST" action="{{ route('ops.team.invite') }}" class="space-y-5 text-xs">
                    @csrf

                    <!-- Invitee Basic Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                Invitee Work Email Address <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" required placeholder="colleague@nodiva.com"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" required placeholder="e.g. Samuel Adjei"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">
                                Official Title / Designation <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="job_title" required placeholder="e.g. Senior Logistics Coordinator"
                                   class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                        </div>
                    </div>

                    <!-- Role Archetype Selection -->
                    <div class="space-y-2 pt-2 border-t border-[#D5DEDF]">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-[#172A35]">
                                Base Platform Role <span class="text-red-500">*</span>
                            </label>
                            <button type="button" @click="openCreateRoleModal()" 
                                    class="text-[11px] font-bold text-[#167C78] hover:text-[#126461] hover:underline flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>+ Add Dynamic Role</span>
                            </button>
                        </div>
                        <select name="role_id" x-model="selectedRoleId" @change="syncPermissionsWithRole()"
                                class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-white">
                            <template x-for="r in roles" :key="r.id">
                                <option :value="r.id" x-text="r.name + ' — ' + r.description" :selected="r.id == selectedRoleId"></option>
                            </template>
                        </select>

                        <!-- Dynamic Role Scope Notification -->
                        <div x-show="isPlatformAdminSelected" class="p-2.5 bg-[#102A36]/5 border border-[#102A36]/20 rounded-xs flex items-start gap-2 text-xs">
                            <span class="text-sm">👑</span>
                            <div>
                                <div class="font-bold text-[#102A36]">Platform Administrator (Full System Scope)</div>
                                <div class="text-[11px] text-[#61727D]">Automatically grants master access across all 8 operational console modules and security governance controls.</div>
                            </div>
                        </div>

                        <div x-show="!isPlatformAdminSelected && currentRole" class="p-2.5 bg-[#167C78]/5 border border-[#167C78]/20 rounded-xs flex items-start gap-2 text-xs">
                            <span class="text-sm">🛡️</span>
                            <div>
                                <div class="font-bold text-[#167C78]" x-text="currentRole?.name + ' (Operational Role Archetype)'"></div>
                                <div class="text-[11px] text-[#61727D]">Applies standard role preset. Check or uncheck individual sidebar menus below to dynamically customize access for this team member.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Granular Sidebar Menu & Permission Checkbox Grid -->
                    <div class="space-y-2 pt-2 border-t border-[#D5DEDF]">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#102A36]">
                                Authorized Sidebar Menus & Modules
                            </label>
                            <div class="flex items-center gap-2 text-[10px]">
                                <span class="font-mono text-[#167C78] font-bold" x-text="selectedPermissions.length + ' of ' + Object.keys(sidebarPermissions).length + ' menus active'"></span>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="selectAllPermissions()" class="text-[#167C78] hover:underline font-semibold">Select All</button>
                                <button type="button" @click="clearAllPermissions()" class="text-[#61727D] hover:underline font-semibold">Clear</button>
                                <button type="button" @click="resetPermissionsToRole()" class="text-[#94611A] hover:underline font-semibold">Reset to Default</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 bg-[#F5F3ED] p-3.5 border border-[#D5DEDF] rounded-sm">
                            @foreach ($sidebarPermissions as $key => $perm)
                                <label class="flex items-start gap-2.5 p-2 bg-white border border-[#D5DEDF] hover:border-[#167C78] rounded-xs cursor-pointer transition">
                                    <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                           x-model="selectedPermissions"
                                           class="mt-0.5 text-[#167C78] focus:ring-[#167C78] rounded-xs">
                                    <div class="space-y-0.5">
                                        <div class="font-bold text-xs text-[#102A36]">{{ $perm['name'] }}</div>
                                        <div class="text-[10px] text-[#61727D] leading-tight">{{ $perm['description'] }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Expiration Duration -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-[#D5DEDF]">
                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">Invitation Link Validity</label>
                            <select name="expires_in_days" class="w-full px-3 py-2 text-xs border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-white">
                                <option value="7">7 Days (Standard)</option>
                                <option value="3">3 Days</option>
                                <option value="14">14 Days</option>
                                <option value="30">30 Days</option>
                            </select>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="inviteModalOpen = false" 
                                class="px-4 py-2 border border-[#D5DEDF] text-[#102A36] hover:bg-[#F5F3ED] font-bold rounded-sm">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-5 py-2 bg-[#167C78] hover:bg-[#126461] text-white font-bold rounded-sm shadow-sm flex items-center gap-1.5">
                            <span>Send Formal Invitation &rarr;</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Classic Modal: Edit Member Permissions -->
        <div x-show="editModalOpen" x-cloak style="display: none;" 
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="editModalOpen = false" 
                 class="bg-white border border-[#D5DEDF] max-w-2xl w-full p-6 space-y-5 shadow-2xl max-h-[92vh] overflow-y-auto rounded-sm">
                
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#167C78]">Governance & Permissions</span>
                        <h3 class="text-base font-bold text-[#102A36]">Edit Access: <span x-text="selectedMember?.user?.name"></span></h3>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <form :action="'/ops/team/' + (selectedMember ? selectedMember.id : '') + '/permissions'" method="POST" class="space-y-5 text-xs">
                    @csrf
                    @method('PATCH')

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-[#172A35]">Assigned Base Role</label>
                            <button type="button" @click="openCreateRoleModal()" 
                                    class="text-[11px] font-bold text-[#167C78] hover:text-[#126461] hover:underline flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>+ Add Dynamic Role</span>
                            </button>
                        </div>
                        <select name="role_id" x-model="selectedRoleId" @change="syncPermissionsWithRole()"
                                class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-white">
                            <template x-for="r in roles" :key="r.id">
                                <option :value="r.id" x-text="r.name + ' — ' + r.description" :selected="r.id == selectedRoleId"></option>
                            </template>
                        </select>

                        <!-- Dynamic Role Scope Notification -->
                        <div x-show="isPlatformAdminSelected" class="mt-2 p-2.5 bg-[#102A36]/5 border border-[#102A36]/20 rounded-xs flex items-start gap-2 text-xs">
                            <span class="text-sm">👑</span>
                            <div>
                                <div class="font-bold text-[#102A36]">Platform Administrator (Full System Scope)</div>
                                <div class="text-[11px] text-[#61727D]">Automatically grants master access across all 8 operational console modules and security governance controls.</div>
                            </div>
                        </div>

                        <div x-show="!isPlatformAdminSelected && currentRole" class="mt-2 p-2.5 bg-[#167C78]/5 border border-[#167C78]/20 rounded-xs flex items-start gap-2 text-xs">
                            <span class="text-sm">🛡️</span>
                            <div>
                                <div class="font-bold text-[#167C78]" x-text="currentRole?.name + ' (Operational Role Archetype)'"></div>
                                <div class="text-[11px] text-[#61727D]">Applies standard role preset. Check or uncheck individual sidebar menus below to dynamically customize access for this team member.</div>
                            </div>
                        </div>
                    </div>

                    @if (auth()->user()->isCto())
                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">Account State (CTO Privilege)</label>
                            <select name="status" :value="selectedMember?.status"
                                    class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-white">
                                <option value="active">Active (Access Granted)</option>
                                <option value="suspended">Suspended (Access Revoked / Deactivated)</option>
                            </select>
                            <div class="text-[10px] text-[#167C78] mt-1 font-semibold flex items-center gap-1">
                                <span>👑</span> Chief Technical Officer privilege: You can activate, deactivate, or suspend any console account.
                            </div>
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-semibold text-[#172A35] mb-1">Account State</label>
                            <input type="hidden" name="status" :value="selectedMember?.status || 'active'">
                            <div class="px-3 py-2 text-sm border border-[#D5DEDF] bg-gray-50 text-[#61727D] rounded-sm flex items-center justify-between">
                                <span class="font-semibold uppercase text-xs" x-text="selectedMember?.status || 'active'"></span>
                                <span class="text-[10px] text-[#94611A] font-semibold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-[#94611A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Deactivation locked &bull; Only CTO can suspend accounts
                                </span>
                            </div>
                        </div>
                    @endif

                    <!-- Sidebar Menus Checkboxes -->
                    <div class="space-y-2 pt-2 border-t border-[#D5DEDF]">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#102A36]">
                                Permitted Sidebar Menus
                            </label>
                            <div class="flex items-center gap-2 text-[10px]">
                                <span class="font-mono text-[#167C78] font-bold" x-text="selectedPermissions.length + ' of ' + Object.keys(sidebarPermissions).length + ' menus active'"></span>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="selectAllPermissions()" class="text-[#167C78] hover:underline font-semibold">Select All</button>
                                <button type="button" @click="clearAllPermissions()" class="text-[#61727D] hover:underline font-semibold">Clear</button>
                                <button type="button" @click="resetPermissionsToRole()" class="text-[#94611A] hover:underline font-semibold">Reset to Default</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 bg-[#F5F3ED] p-3.5 border border-[#D5DEDF] rounded-sm">
                            @foreach ($sidebarPermissions as $key => $perm)
                                <label class="flex items-start gap-2.5 p-2 bg-white border border-[#D5DEDF] hover:border-[#167C78] rounded-xs cursor-pointer transition">
                                    <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                           x-model="selectedPermissions"
                                           class="mt-0.5 text-[#167C78] focus:ring-[#167C78] rounded-xs">
                                    <div class="space-y-0.5">
                                        <div class="font-bold text-xs text-[#102A36]">{{ $perm['name'] }}</div>
                                        <div class="text-[10px] text-[#61727D] leading-tight">{{ $perm['description'] }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex items-center justify-between">
                        <div>
                            @if (auth()->user()->isCto() || auth()->user()->isPlatformAdmin())
                                <template x-if="selectedMember && selectedMember.user_id !== {{ auth()->id() }} && !selectedMember.user?.is_cto">
                                    <button type="button" 
                                            @click="if(confirm('Are you sure you want to permanently remove ' + selectedMember.user.name + ' (' + selectedMember.user.email + ') from the Operations Console? Their credentials will be revoked immediately.')) { $refs.modalDeleteForm.submit(); }"
                                            class="text-xs font-semibold text-[#B42332] hover:text-red-700 hover:underline cursor-pointer">
                                        &times; Remove Team Member
                                    </button>
                                </template>
                            @endif
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="editModalOpen = false" 
                                    class="px-4 py-2 border border-[#D5DEDF] text-[#102A36] hover:bg-[#F5F3ED] font-bold rounded-sm cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-5 py-2 bg-[#167C78] hover:bg-[#126461] text-white font-bold rounded-sm shadow-sm cursor-pointer">
                                Save Changes &rarr;
                            </button>
                        </div>
                    </div>
                </form>

                @if (auth()->user()->isCto() || auth()->user()->isPlatformAdmin())
                    <form x-ref="modalDeleteForm" :action="'/ops/team/' + (selectedMember ? selectedMember.id : '')" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </div>
        </div>

        <!-- Classic Modal: Define New Platform Role -->
        <div x-show="createRoleModalOpen" x-cloak style="display: none;" 
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div @click.away="createRoleModalOpen = false" 
                 class="bg-white border border-[#D5DEDF] max-w-xl w-full p-6 space-y-5 shadow-2xl max-h-[92vh] overflow-y-auto rounded-sm">
                
                <div class="flex justify-between items-center border-b border-[#D5DEDF] pb-3">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#167C78]">Role Governance</span>
                        <h3 class="text-base font-bold text-[#102A36]">Define New Platform Role Archetype</h3>
                    </div>
                    <button type="button" @click="createRoleModalOpen = false" class="text-gray-400 hover:text-black font-bold text-lg">&times;</button>
                </div>

                <div x-show="newRoleErrors" class="p-3 bg-[#B42332]/10 border border-[#B42332]/30 text-xs text-[#B42332] font-semibold rounded-sm" x-text="newRoleErrors"></div>

                <form @submit.prevent="submitNewRole()" class="space-y-4 text-xs">
                    <div>
                        <label class="block text-xs font-semibold text-[#172A35] mb-1">
                            Role Title / Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="newRole.name" required placeholder="e.g. Regional Logistics Dispatcher"
                               class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#172A35] mb-1">
                            Scope Description <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="newRole.description" required placeholder="e.g. Coordinates regional specimen transit manifests and handoffs."
                               class="w-full px-3 py-2 text-sm border border-[#D5DEDF] rounded-sm focus:outline-none focus:border-[#167C78] bg-[#F5F3ED]/30">
                    </div>

                    <div class="pt-2 border-t border-[#D5DEDF]">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="newRole.is_admin" class="text-[#167C78] focus:ring-[#167C78] rounded-xs">
                            <div>
                                <span class="font-bold text-xs text-[#102A36]">Master Administrative Authority</span>
                                <p class="text-[10px] text-[#61727D]">Grants full administrative privileges across all platform workflows.</p>
                            </div>
                        </label>
                    </div>

                    <div class="space-y-2 pt-2 border-t border-[#D5DEDF]" x-show="!newRole.is_admin">
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#102A36]">
                            Default Permitted Sidebar Modules
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-[#F5F3ED] p-3 border border-[#D5DEDF] rounded-sm max-h-48 overflow-y-auto">
                            @foreach ($sidebarPermissions as $key => $perm)
                                <label class="flex items-start gap-2 p-1.5 bg-white border border-[#D5DEDF] hover:border-[#167C78] rounded-xs cursor-pointer transition">
                                    <input type="checkbox" value="{{ $key }}"
                                           x-model="newRole.permissions"
                                           class="mt-0.5 text-[#167C78] focus:ring-[#167C78] rounded-xs">
                                    <div>
                                        <div class="font-bold text-[11px] text-[#102A36]">{{ $perm['name'] }}</div>
                                        <div class="text-[9px] text-[#61727D] leading-tight">{{ $perm['description'] }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#D5DEDF] flex justify-end gap-3">
                        <button type="button" @click="createRoleModalOpen = false" 
                                class="px-4 py-2 border border-[#D5DEDF] text-[#102A36] hover:bg-[#F5F3ED] font-bold rounded-sm">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmittingRole"
                                class="px-5 py-2 bg-[#167C78] hover:bg-[#126461] disabled:opacity-50 text-white font-bold rounded-sm shadow-sm flex items-center gap-1.5">
                            <span x-text="isSubmittingRole ? 'Registering...' : 'Register Platform Role &rarr;'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function teamGovernance() {
            return {
                inviteModalOpen: false,
                editModalOpen: false,
                createRoleModalOpen: false,
                selectedMember: null,
                selectedRoleId: {{ $roles->first()?->id ?? 1 }},
                roles: @json($roles),
                sidebarPermissions: @json($sidebarPermissions),
                selectedPermissions: [],
                copyMessage: '',

                newRole: {
                    name: '',
                    description: '',
                    is_admin: false,
                    permissions: [],
                },
                newRoleErrors: '',
                isSubmittingRole: false,

                init() {
                    this.syncPermissionsWithRole();
                },

                openCreateRoleModal() {
                    this.newRole = {
                        name: '',
                        description: '',
                        is_admin: false,
                        permissions: [],
                    };
                    this.newRoleErrors = '';
                    this.createRoleModalOpen = true;
                },

                async submitNewRole() {
                    this.isSubmittingRole = true;
                    this.newRoleErrors = '';
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch('{{ route('ops.team.roles.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(this.newRole),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.newRoleErrors = data.message || 'Validation error while defining platform role.';
                            this.isSubmittingRole = false;
                            return;
                        }

                        if (data.success && data.role) {
                            this.roles.push(data.role);
                            this.selectedRoleId = data.role.id;
                            this.syncPermissionsWithRole();
                            this.createRoleModalOpen = false;
                            this.copyMessage = "Platform role '" + data.role.name + "' dynamically added and selected!";
                            setTimeout(() => { this.copyMessage = ''; }, 5000);
                        }
                    } catch (err) {
                        this.newRoleErrors = 'Network or server error while defining role.';
                    } finally {
                        this.isSubmittingRole = false;
                    }
                },

                get currentRole() {
                    return this.roles.find(r => r.id == this.selectedRoleId);
                },

                get isPlatformAdminSelected() {
                    return Boolean(this.currentRole?.is_admin);
                },

                syncPermissionsWithRole() {
                    const role = this.currentRole;
                    if (role) {
                        if (role.is_admin) {
                            this.selectedPermissions = Object.keys(this.sidebarPermissions);
                        } else if (role.permissions) {
                            this.selectedPermissions = role.permissions.map(p => p.key).filter(k => this.sidebarPermissions[k]);
                        }
                    }
                },

                selectAllPermissions() {
                    this.selectedPermissions = Object.keys(this.sidebarPermissions);
                },

                clearAllPermissions() {
                    this.selectedPermissions = [];
                },

                resetPermissionsToRole() {
                    this.syncPermissionsWithRole();
                },

                openEditModal(member) {
                    this.selectedMember = member;
                    this.selectedRoleId = member.role_id;
                    const role = this.roles.find(r => r.id == member.role_id);
                    if (member.permissions && member.permissions.length > 0) {
                        this.selectedPermissions = [...member.permissions];
                    } else if (role && role.is_admin) {
                        this.selectedPermissions = Object.keys(this.sidebarPermissions);
                    } else if (role && role.permissions) {
                        this.selectedPermissions = role.permissions.map(p => p.key).filter(k => this.sidebarPermissions[k]);
                    } else {
                        this.selectedPermissions = [];
                    }
                    this.editModalOpen = true;
                },

                copyInviteLink(url) {
                    navigator.clipboard.writeText(url).then(() => {
                        this.copyMessage = 'Link copied to clipboard!';
                        setTimeout(() => { this.copyMessage = ''; }, 3000);
                    });
                }
            };
        }
    </script>
</x-layouts.workspace>
