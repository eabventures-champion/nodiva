<x-layouts.workspace title="{{ $thread->subject }} — NODIVA Operations" :organization="$organization">
    <x-slot:sidebar>
        <x-ops-sidebar active="communications" />
    </x-slot:sidebar>

    <div class="space-y-6 max-w-5xl">
        <!-- Back Navigation & Title -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('ops.communications.index') }}" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#61727D] hover:text-[#167C78] transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Back to Stream</span>
                </a>
                <span class="text-[#D5DEDF]">/</span>
                <span class="font-mono text-xs font-bold text-[#167C78] bg-[#167C78]/10 px-2 py-0.5 rounded-xs border border-[#167C78]/25">
                    {{ $thread->reference }}
                </span>
            </div>

            <!-- Action: Close / Reopen thread -->
            <form method="POST" action="{{ route('ops.communications.toggle-status', $thread->uuid) }}" class="inline">
                @csrf
                @if ($thread->status === 'open')
                    <button type="submit" class="px-3 py-1.5 border border-[#D5DEDF] bg-white text-xs font-semibold text-[#61727D] hover:text-[#B42332] hover:border-[#B42332] rounded-xs transition shadow-2xs inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-[#B42332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Mark as Resolved / Close Thread</span>
                    </button>
                @else
                    <button type="submit" class="px-3 py-1.5 border border-[#167C78] bg-[#E8F0EF] text-xs font-semibold text-[#167C78] hover:bg-[#167C78] hover:text-white rounded-xs transition shadow-2xs inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reopen Thread</span>
                    </button>
                @endif
            </form>
        </div>

        @if ($thread->status === 'closed')
            <!-- Resolution Status Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-slate-50 border border-slate-200 text-xs text-slate-700 rounded-xs">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span>
                        <strong class="text-slate-900">Thread Marked as Resolved / Closed</strong>
                        @if ($thread->closed_at)
                            <span class="text-slate-500">• {{ $thread->closed_at->format('M d, Y H:i') }}</span>
                        @endif
                        @if ($thread->closer)
                            by <span class="font-semibold text-slate-900">{{ $thread->closer->name }}</span>
                            @if ($thread->closerOrganizationName())
                                <span class="text-slate-500">({{ $thread->closerOrganizationName() }})</span>
                            @endif
                        @endif
                    </span>
                </div>
                <form method="POST" action="{{ route('ops.communications.toggle-status', $thread->uuid) }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-[#167C78] hover:text-[#102A36] font-semibold underline underline-offset-2 cursor-pointer">
                        Reopen Thread
                    </button>
                </form>
            </div>
        @endif

        <!-- Thread Context Card -->
        <div class="bg-white border border-[#D5DEDF] shadow-xs">
            <div class="p-6 border-b border-[#D5DEDF] bg-[#FAFAFA]">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-xs {{ $thread->priorityBadgeClass() }}">
                                {{ $thread->priority }}
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-xs bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $thread->categoryLabel() }}
                            </span>
                            @if ($thread->isBroadcast())
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-xs bg-blue-50 text-blue-700 border border-blue-200">
                                    Broadcast Notice
                                </span>
                            @endif
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-xs {{ $thread->status === 'open' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-gray-100 text-gray-700 border border-gray-200' }}">
                                {{ strtoupper($thread->status) }}
                            </span>
                        </div>
                        <h1 class="text-xl font-bold text-[#102A36] mt-1">{{ $thread->subject }}</h1>
                    </div>

                    <div class="text-right text-xs text-[#61727D] space-y-0.5 shrink-0">
                        <div>Created: <span class="font-mono text-[#102A36]">{{ $thread->created_at->format('M d, Y H:i') }}</span></div>
                        <div>Initiated By: <span class="font-medium text-[#102A36]">{{ $thread->senderUser?->name ?? 'Ops Team' }}</span></div>
                    </div>
                </div>

                <!-- Recipient Info Banner -->
                <div class="mt-4 pt-4 border-t border-[#D5DEDF]/60 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-[#102A36] uppercase tracking-wider text-[11px]">Recipient Target:</span>
                        <span class="px-2.5 py-1 bg-[#E8F0EF] text-[#167C78] font-bold rounded-xs">
                            {{ $thread->recipientLabel() }}
                        </span>
                        @if ($thread->recipientOrganization)
                            <span class="text-[#61727D]">({{ ucfirst($thread->recipientOrganization->type->value) }} • {{ $thread->recipientOrganization->city ?? 'Ghana' }})</span>
                        @endif
                    </div>
                    <div class="text-[11px] text-[#61727D] font-mono">
                        {{ $thread->messages->count() }} message(s) in this conversation
                    </div>
                </div>
            </div>

            <!-- Messages Stream -->
            <div class="p-6 space-y-6 bg-[#F5F3ED]/30">
                @foreach ($thread->messages as $msg)
                    @php
                        $isOps = $msg->organization?->isPlatform();
                    @endphp
                    <div class="flex flex-col {{ $isOps ? 'items-end' : 'items-start' }}">
                        <div class="max-w-2xl w-full">
                            <!-- Sender Meta Header -->
                            <div class="flex items-center justify-between gap-2 mb-1 px-1 text-[11px]">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-[#102A36]">{{ $msg->user?->name ?? 'Anonymous' }}</span>
                                    <span class="px-1.5 py-0.2 rounded-xs font-semibold text-[10px] {{ $isOps ? 'bg-[#102A36] text-white' : 'bg-[#167C78] text-white' }}">
                                        {{ $msg->organization?->name ?? ($isOps ? 'NODIVA Operations' : 'Partner') }}
                                    </span>
                                </div>
                                <span class="text-[#61727D] font-mono">{{ $msg->created_at->format('M d, Y • H:i') }}</span>
                            </div>

                            <!-- Message Bubble Card -->
                            <div class="p-4 rounded-xs border shadow-xs leading-relaxed text-xs {{ $isOps ? 'bg-[#102A36] text-[#F5F3ED] border-[#193B49]' : 'bg-white text-[#172A35] border-[#D5DEDF]' }}">
                                <div class="whitespace-pre-wrap">{!! nl2br(e($msg->message)) !!}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Reply Box -->
            <div class="p-6 bg-white border-t border-[#D5DEDF]">
                @if ($thread->status === 'closed')
                    <div class="mb-4 p-3 bg-amber-50/70 border border-amber-200 text-xs text-amber-800 rounded-xs flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>This thread is currently marked as resolved. Posting an operational directive below will automatically reopen it for the participants.</span>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('ops.communications.reply', $thread->uuid) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-[#102A36] uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>{{ $thread->status === 'closed' ? 'Post a Directive to Reopen' : 'Post an Operational Reply / Directive' }}</span>
                            <span class="text-[#61727D] font-normal lowercase">Markdown & formatting supported</span>
                        </label>
                        <textarea name="message" rows="4" required placeholder="Type your response to the administrator(s)..."
                                  class="w-full text-xs bg-[#F5F3ED] border border-[#D5DEDF] p-3 rounded-xs font-sans text-[#102A36] focus:bg-white focus:border-[#167C78] focus:outline-none leading-relaxed">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-xs text-[#B42332] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-[#61727D]">
                            Sending as <span class="font-bold text-[#102A36]">{{ auth()->user()->name }}</span> (NODIVA Operations Network)
                        </span>
                        <button type="submit" 
                                class="px-5 py-2 bg-[#167C78] hover:bg-[#102A36] text-white text-xs font-bold uppercase tracking-wider rounded-xs transition shadow-sm inline-flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <span>{{ $thread->status === 'closed' ? 'Directive & Reopen' : 'Send Reply' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.workspace>
