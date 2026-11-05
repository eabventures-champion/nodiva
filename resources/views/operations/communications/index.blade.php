<x-layouts.workspace title="Institutional Communications & Notices — NODIVA Operations" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="communications" />
    </x-slot:sidebar>

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-[#102A36]">Institutional Communications & Notices</h1>
                    <span class="px-2 py-0.5 text-[10px] font-mono font-bold bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/25 rounded-xs">
                        Direct & Network Broadcast
                    </span>
                </div>
                <p class="text-xs text-[#61727D]">
                    Direct two-way operational messaging and network broadcast announcements to Hospital, Laboratory, and Corporate administrators.
                </p>
            </div>
            <a href="{{ route('ops.communications.create') }}" 
               class="px-4 py-2 bg-[#167C78] hover:bg-[#102A36] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition inline-flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Compose New Communication</span>
            </a>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 border border-[#D5DEDF] shadow-xs">
                <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Active Open Threads</div>
                <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $openCount }}</div>
                <div class="text-[10px] text-[#167C78] font-medium mt-1">Pending resolution / responses</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF] shadow-xs">
                <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Urgent Alerts</div>
                <div class="text-2xl font-bold text-[#B42332] mt-1">{{ $urgentCount }}</div>
                <div class="text-[10px] text-[#B42332] font-medium mt-1">Requiring prioritized attention</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF] shadow-xs">
                <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Network Broadcasts</div>
                <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $broadcastCount }}</div>
                <div class="text-[10px] text-[#61727D] mt-1">Multi-facility announcements</div>
            </div>
            <div class="bg-white p-4 border border-[#D5DEDF] shadow-xs">
                <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Resolved / Closed</div>
                <div class="text-2xl font-bold text-[#61727D] mt-1">{{ $closedCount }}</div>
                <div class="text-[10px] text-[#61727D] mt-1">Historical archived threads</div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white p-4 border border-[#D5DEDF] shadow-xs">
            <form method="GET" action="{{ route('ops.communications.index') }}" class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-[#102A36] uppercase tracking-wider mr-1">Filter:</span>
                    <a href="{{ route('ops.communications.index', array_merge(request()->query(), ['status' => 'open'])) }}"
                       class="px-2.5 py-1 text-xs font-semibold rounded-xs border transition {{ $statusFilter === 'open' ? 'bg-[#167C78] text-white border-[#167C78]' : 'bg-[#F5F3ED] text-[#61727D] border-[#D5DEDF] hover:text-[#102A36]' }}">
                        Open ({{ $openCount }})
                    </a>
                    <a href="{{ route('ops.communications.index', array_merge(request()->query(), ['status' => 'closed'])) }}"
                       class="px-2.5 py-1 text-xs font-semibold rounded-xs border transition {{ $statusFilter === 'closed' ? 'bg-[#167C78] text-white border-[#167C78]' : 'bg-[#F5F3ED] text-[#61727D] border-[#D5DEDF] hover:text-[#102A36]' }}">
                        Closed ({{ $closedCount }})
                    </a>

                    <span class="text-[#D5DEDF] mx-1">|</span>

                    <select name="type" onchange="this.form.submit()" class="text-xs bg-[#F5F3ED] border border-[#D5DEDF] px-2 py-1 rounded-xs font-medium text-[#102A36] focus:border-[#167C78] focus:outline-none">
                        <option value="">All Recipient Types</option>
                        <option value="broadcast" {{ $typeFilter === 'broadcast' ? 'selected' : '' }}>Network Broadcasts</option>
                        <option value="hospital" {{ $typeFilter === 'hospital' ? 'selected' : '' }}>Hospitals</option>
                        <option value="laboratory" {{ $typeFilter === 'laboratory' ? 'selected' : '' }}>Laboratories</option>
                        <option value="corporate" {{ $typeFilter === 'corporate' ? 'selected' : '' }}>Corporates</option>
                    </select>

                    <select name="priority" onchange="this.form.submit()" class="text-xs bg-[#F5F3ED] border border-[#D5DEDF] px-2 py-1 rounded-xs font-medium text-[#102A36] focus:border-[#167C78] focus:outline-none">
                        <option value="">All Priorities</option>
                        <option value="urgent" {{ $priorityFilter === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        <option value="high" {{ $priorityFilter === 'high' ? 'selected' : '' }}>High</option>
                        <option value="normal" {{ $priorityFilter === 'normal' ? 'selected' : '' }}>Normal</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Search subject, ref or partner..."
                           class="text-xs bg-[#F5F3ED] border border-[#D5DEDF] px-3 py-1.5 rounded-xs w-full sm:w-64 focus:bg-white focus:border-[#167C78] focus:outline-none">
                    <button type="submit" class="px-3 py-1.5 bg-[#193B49] hover:bg-[#167C78] text-white text-xs font-semibold rounded-xs transition shadow-2xs">
                        Search
                    </button>
                    @if ($searchQuery || $typeFilter || $priorityFilter || $statusFilter !== 'open')
                        <a href="{{ route('ops.communications.index') }}" class="text-xs text-[#61727D] hover:text-[#B42332] underline ml-1">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Thread List -->
        <div class="bg-white border border-[#D5DEDF] shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex items-center justify-between bg-[#FAFAFA]">
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">
                    Communication Stream & Threads
                </h2>
                <span class="text-xs text-[#61727D] font-mono">
                    Showing {{ $threads->count() }} of {{ $threads->total() }} thread(s)
                </span>
            </div>

            @if ($threads->isEmpty())
                <div class="p-12 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-[#E8F0EF] text-[#167C78] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-[#102A36]">No communication threads match the criteria.</p>
                    <p class="text-xs text-[#61727D]">You can compose a new announcement or notice to institutional administrators.</p>
                    <div class="pt-2">
                        <a href="{{ route('ops.communications.create') }}" class="px-3 py-1.5 bg-[#167C78] text-white text-xs font-bold rounded-xs shadow-2xs hover:bg-[#102A36] transition inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Compose Message</span>
                        </a>
                    </div>
                </div>
            @else
                <div class="divide-y divide-[#D5DEDF]/70">
                    @foreach ($threads as $thread)
                        @php
                            $lastMsg = $thread->latestMessage;
                            $isPartnerReply = $lastMsg && $lastMsg->organization && ! $lastMsg->organization->isPlatform();
                        @endphp
                        <a href="{{ route('ops.communications.show', $thread->uuid) }}" 
                           class="block p-4 sm:p-5 hover:bg-[#F5F3ED]/80 transition group relative {{ $thread->status === 'closed' ? 'bg-[#FAFAFA] opacity-75' : '' }}">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-xs font-bold text-[#167C78] bg-[#167C78]/10 px-2 py-0.5 rounded-xs border border-[#167C78]/20">
                                        {{ $thread->reference }}
                                    </span>
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-xs {{ $thread->priorityBadgeClass() }}">
                                        {{ $thread->priority }}
                                    </span>
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-xs bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $thread->categoryLabel() }}
                                    </span>
                                    @if ($thread->isBroadcast())
                                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-xs bg-blue-50 text-blue-700 border border-blue-200">
                                            Broadcast
                                        </span>
                                    @endif
                                    @if ($thread->status === 'closed')
                                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-xs bg-gray-100 text-gray-600 border border-gray-200">
                                            Closed
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-[#61727D] font-mono flex items-center gap-2">
                                    <span>Last activity: {{ $thread->last_message_at?->diffForHumans() ?? 'Recently' }}</span>
                                </div>
                            </div>

                            <div class="mt-2 space-y-1">
                                <h3 class="text-sm font-bold text-[#102A36] group-hover:text-[#167C78] transition flex items-center gap-2">
                                    <span>{{ $thread->subject }}</span>
                                    @if ($isPartnerReply)
                                        <span class="px-1.5 py-0.2 text-[9px] font-bold bg-[#167C78] text-white rounded-full">
                                            NEW PARTNER REPLY
                                        </span>
                                    @endif
                                </h3>

                                <div class="flex items-center gap-2 text-xs text-[#61727D]">
                                    <span class="font-semibold text-[#102A36]">Recipient:</span>
                                    <span class="px-2 py-0.5 bg-[#E8F0EF] text-[#167C78] font-medium rounded-xs">
                                        {{ $thread->recipientLabel() }}
                                    </span>
                                    <span class="text-[#D5DEDF]">•</span>
                                    <span>Initiator: {{ $thread->senderUser?->name ?? 'Ops Team' }} ({{ $thread->senderOrganization?->name ?? 'NODIVA' }})</span>
                                </div>

                                @if ($lastMsg)
                                    <p class="text-xs text-[#61727D] line-clamp-1 mt-1 bg-white p-2 rounded-xs border border-[#D5DEDF]/50">
                                        <span class="font-bold text-[#102A36]">{{ $lastMsg->user?->name ?? 'User' }} ({{ $lastMsg->organization?->name ?? 'NODIVA' }}):</span>
                                        {{ \Illuminate\Support\Str::limit($lastMsg->message, 120) }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="px-6 py-4 bg-[#FAFAFA] border-t border-[#D5DEDF]">
                    {{ $threads->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.workspace>
