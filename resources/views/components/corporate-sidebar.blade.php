@props(['active' => 'dashboard', 'organization' => null])

<aside class="w-64 flex-shrink-0 bg-white border-r border-[#D5DEDF] flex flex-col justify-between py-6 h-full overflow-y-auto">
    <nav class="space-y-1 px-4 text-xs font-semibold">
        <a href="{{ route('corporate.dashboard') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'dashboard' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Screening Programs
        </a>
        <a href="{{ route('corporate.employees.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'employees' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Employee Rosters
        </a>
        <a href="{{ route('corporate.sessions.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'sessions' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Appointment Sessions
        </a>
        <a href="{{ route('corporate.insights.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'insights' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Aggregate Health Insights
        </a>
        <a href="{{ route('corporate.invoices.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'billing' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Program Invoices
        </a>
        <a href="{{ route('corporate.communications.index') }}" 
           class="flex items-center justify-between px-3 py-2 rounded-sm {{ $active === 'communications' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            <span>Platform Communications</span>
            @php
                $corpOrg = auth()->user()?->currentMembership?->organization ?? auth()->user()?->activeMemberships()->first()?->organization;
                $corpCommsCount = 0;
                if ($corpOrg) {
                    $corpCommsCount = \App\Models\CommunicationThread::where('status', 'open')
                        ->where(function ($q) use ($corpOrg) {
                            $q->where('recipient_organization_id', $corpOrg->id)
                              ->orWhere('sender_organization_id', $corpOrg->id)
                              ->orWhere('recipient_type', 'all_corporates')
                              ->orWhere('recipient_type', 'all_partners');
                        })->count();
                }
            @endphp
            @if ($corpCommsCount > 0)
                <span class="px-1.5 py-0.5 text-[10px] font-bold bg-[#167C78] text-white rounded-full">
                    {{ $corpCommsCount }}
                </span>
            @endif
        </a>
        <a href="{{ route('corporate.team.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'team' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Team & Access Governance
        </a>
        <div class="pt-2 mt-2 border-t border-[#D5DEDF]">
            <a href="{{ route('profile.show') }}" 
               class="block px-3 py-2 rounded-sm {{ $active === 'profile' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
                My Profile & Credentials
            </a>
        </div>
    </nav>

    <div class="px-6 text-[11px] text-[#61727D] space-y-1">
        <div>Environment: Production-ready</div>
        <div>Encryption: AES-256 GCM</div>
        <div>Launch Region: Ghana (GH)</div>
    </div>
</aside>
