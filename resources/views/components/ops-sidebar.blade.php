@props(['active' => 'dashboard', 'organization' => null])

@php
    $user = auth()->user();
@endphp

<aside class="w-64 flex-shrink-0 bg-white border-r border-[#D5DEDF] flex flex-col justify-between py-6 h-full overflow-y-auto">
    <nav class="space-y-1 px-4 text-xs font-semibold">
        @if ($user?->canAccessMenu('ops.orders.monitor'))
            <a href="{{ route('ops.dashboard') }}" 
               class="block px-3 py-2 rounded-sm {{ $active === 'dashboard' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
                Network Operations
            </a>
        @endif

        @if ($user?->canAccessMenu('ops.organizations.manage'))
            <a href="{{ route('ops.partners.index') }}" 
               class="flex items-center justify-between px-3 py-2 rounded-sm {{ $active === 'partners' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
                <span>Institutional Partners</span>
                @php
                    $pendingPartnersCount = \App\Models\Organization::where('type', '!=', 'platform')->where('status', 'pending')->count();
                @endphp
                @if ($pendingPartnersCount > 0)
                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-[#94611A] text-white rounded-full">
                        {{ $pendingPartnersCount }}
                    </span>
                @endif
            </a>
        @endif

        @if ($user?->canAccessMenu('ops.catalogue.manage'))
            <a href="{{ route('ops.catalogue.index') }}" 
               class="block px-3 py-2 rounded-sm {{ $active === 'catalogue' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
                Master Test Catalogue
            </a>
        @endif

        @if ($user?->canAccessMenu('ops.qualifications.verify'))
            <a href="{{ route('ops.qualifications.index') }}" 
               class="block px-3 py-2 rounded-sm {{ $active === 'qualifications' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
                Laboratory Qualifications
            </a>
        @endif

        @if ($user?->canAccessMenu('ops.logistics.manage'))
            <a href="{{ route('ops.logistics.index') }}" 
               class="block px-3 py-2 rounded-sm {{ $active === 'logistics' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
                Logistics & Couriers
            </a>
        @endif

        @if ($user?->canAccessMenu('ops.finance.manage'))
            <a href="{{ route('ops.finance.index') }}" 
               class="block px-3 py-2 rounded-sm {{ $active === 'finance' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
                Financial Reconciliation
            </a>
        @endif

        @if ($user?->canAccessMenu('ops.audit.view'))
            <a href="{{ route('ops.audit.index') }}" 
               class="block px-3 py-2 rounded-sm {{ $active === 'audit' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
                Security & Audit Trail
            </a>
        @endif

        <a href="{{ route('ops.communications.index') }}" 
           class="flex items-center justify-between px-3 py-2 rounded-sm {{ $active === 'communications' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            <span>Communications & Notices</span>
            @php
                $openThreadsCount = \App\Models\CommunicationThread::where('status', 'open')->count();
            @endphp
            @if ($openThreadsCount > 0)
                <span class="px-1.5 py-0.5 text-[10px] font-bold bg-[#167C78] text-white rounded-full">
                    {{ $openThreadsCount }}
                </span>
            @endif
        </a>

        @if ($user?->canAccessMenu('users.manage'))
            <a href="{{ route('ops.team.index') }}" 
               class="flex items-center justify-between px-3 py-2 rounded-sm {{ $active === 'team' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
                <span>Team & Access Governance</span>
                @php
                    $pendingInvitesCount = \App\Models\OrganizationInvitation::where('status', 'pending')
                        ->where('expires_at', '>', now())
                        ->count();
                @endphp
                @if ($pendingInvitesCount > 0)
                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-[#167C78] text-white rounded-full">
                        {{ $pendingInvitesCount }}
                    </span>
                @endif
            </a>
        @endif

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
