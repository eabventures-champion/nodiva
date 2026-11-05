<x-layouts.workspace title="Communications — Laboratory Portal" :organization="$organization">
    <x-slot:sidebar>
        <x-lab-sidebar active="communications" :organization="$organization" />
    </x-slot:sidebar>

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-[#102A36]">Institutional Communications</h1>
                    <span class="px-2 py-0.5 text-[10px] font-mono font-bold bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/25 rounded-xs">
                        Cross-Tenant Messaging
                    </span>
                </div>
                <p class="text-xs text-[#61727D]">
                    Direct two-way messaging, specimen queries, and official notifications with referring hospitals, corporate screening clients, and NODIVA Central Operations.
                </p>
            </div>
            <a href="{{ route('lab.communications.create') }}" 
               class="px-4 py-2 bg-[#167C78] hover:bg-[#102A36] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition inline-flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Compose Communication</span>
            </a>
        </div>

        <!-- Filter tabs -->
        <div class="flex items-center gap-2 border-b border-[#D5DEDF] pb-3 text-xs">
            <span class="font-bold text-[#102A36] uppercase tracking-wider mr-2">Status:</span>
            <a href="{{ route('lab.communications.index', ['status' => 'all']) }}" 
               class="px-3 py-1 font-semibold rounded-xs border transition {{ $statusFilter === 'all' ? 'bg-[#167C78] text-white border-[#167C78]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:text-[#102A36]' }}">
                All Communications
            </a>
            <a href="{{ route('lab.communications.index', ['status' => 'open']) }}" 
               class="px-3 py-1 font-semibold rounded-xs border transition {{ $statusFilter === 'open' ? 'bg-[#167C78] text-white border-[#167C78]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:text-[#102A36]' }}">
                Active / Open
            </a>
            <a href="{{ route('lab.communications.index', ['status' => 'closed']) }}" 
               class="px-3 py-1 font-semibold rounded-xs border transition {{ $statusFilter === 'closed' ? 'bg-[#167C78] text-white border-[#167C78]' : 'bg-white text-[#61727D] border-[#D5DEDF] hover:text-[#102A36]' }}">
                Resolved / Closed
            </a>
        </div>

        <!-- Communication List -->
        <div class="bg-white border border-[#D5DEDF] shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex items-center justify-between bg-[#FAFAFA]">
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">
                    Laboratory Communication Stream
                </h2>
                <span class="text-xs text-[#61727D] font-mono">
                    {{ $threads->total() }} recorded thread(s)
                </span>
            </div>

            @if ($threads->isEmpty())
                <div class="p-12 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-[#E8F0EF] text-[#167C78] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-[#102A36]">No communications found.</p>
                    <p class="text-xs text-[#61727D]">You can initiate an inquiry or support request with partner hospitals, corporate clients, or NODIVA Operations.</p>
                    <div class="pt-2">
                        <a href="{{ route('lab.communications.create') }}" class="px-3 py-1.5 bg-[#167C78] text-white text-xs font-bold rounded-xs shadow-2xs hover:bg-[#102A36] transition inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Compose Communication</span>
                        </a>
                    </div>
                </div>
            @else
                <div class="divide-y divide-[#D5DEDF]/70">
                    @foreach ($threads as $thread)
                        @php
                            $lastMsg = $thread->latestMessage;
                            $hasUnread = $thread->isUnreadFor(auth()->user());
                            $counterpart = $thread->counterpartFor($organization);
                        @endphp
                        <a href="{{ route('lab.communications.show', $thread->uuid) }}" 
                           class="block p-4 sm:p-5 hover:bg-[#F5F3ED]/80 transition group relative {{ $hasUnread ? 'bg-[#E8F0EF]/30 font-medium' : '' }}">
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
                                            Platform Broadcast
                                        </span>
                                    @elseif ($counterpart)
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-xs bg-[#F5F3ED] text-[#102A36] border border-[#D5DEDF]">
                                            {{ $thread->sender_organization_id === $organization->id ? 'To: ' : 'From: ' }}{{ $counterpart->name }} ({{ $counterpart->type?->label() ?? 'Partner' }})
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-[#61727D] font-mono flex items-center gap-2">
                                    @if ($hasUnread)
                                        <span class="w-2 h-2 rounded-full bg-[#167C78] inline-block animate-pulse"></span>
                                        <span class="text-[#167C78] font-bold">Unread Activity</span>
                                        <span>•</span>
                                    @endif
                                    <span>{{ $thread->last_message_at?->diffForHumans() ?? 'Recently' }}</span>
                                </div>
                            </div>

                            <div class="mt-2 space-y-1">
                                <h3 class="text-sm font-bold text-[#102A36] group-hover:text-[#167C78] transition">
                                    {{ $thread->subject }}
                                </h3>

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
