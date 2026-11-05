@props(['active' => 'dashboard', 'organization' => null])

@php
    $currentOrg = $organization ?? auth()->user()?->currentMembership?->organization ?? auth()->user()?->activeMemberships()->first()?->organization;
    $pendingPhlebotomy = 0;
    if ($currentOrg) {
        $pendingPhlebotomy = \App\Models\Specimen::whereHas('order', function ($q) use ($currentOrg) {
            $q->where('requesting_organization_id', $currentOrg->id);
        })->where('status', \App\Domain\Specimens\Enums\SpecimenStatus::PendingCollection)->count();
    }
@endphp

<aside class="w-64 flex-shrink-0 bg-white border-r border-[#D5DEDF] flex flex-col justify-between py-6 h-full lg:sticky lg:top-14 lg:h-[calc(100vh-3.5rem)] overflow-y-auto self-start">
    <nav class="space-y-1 px-4 text-xs font-semibold">
        <a href="{{ route('hospital.dashboard') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'dashboard' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Referral Operations
        </a>
        <a href="{{ route('hospital.orders.create') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'new' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Create New Referral
        </a>
        <a href="{{ route('hospital.catalogue.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'catalogue' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Diagnostic Test Catalogue
        </a>
        <a href="{{ route('hospital.specimens.index', $pendingPhlebotomy > 0 ? ['status' => 'pending_collection'] : []) }}" 
           class="flex items-center justify-between px-3 py-2 rounded-sm {{ $active === 'custody' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            <span>Specimen Custody & Courier</span>
            @if ($pendingPhlebotomy > 0)
                <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold bg-[#94611A] text-white rounded-full min-w-[18px] h-[18px] animate-pulse" title="{{ $pendingPhlebotomy }} specimen(s) pending phlebotomy">
                    {{ $pendingPhlebotomy }}
                </span>
            @endif
        </a>
        <a href="{{ route('hospital.invoices.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'invoices' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Invoices & Statements
        </a>
        <a href="{{ route('hospital.communications.index') }}" 
           class="flex items-center justify-between px-3 py-2 rounded-sm {{ $active === 'communications' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            <span>Platform Communications</span>
            @php
                $unreadCommsCount = 0;
                if ($currentOrg) {
                    $unreadCommsCount = \App\Models\CommunicationThread::where('status', 'open')
                        ->where(function ($q) use ($currentOrg) {
                            $q->where('recipient_organization_id', $currentOrg->id)
                              ->orWhere('sender_organization_id', $currentOrg->id)
                              ->orWhere('recipient_type', 'all_hospitals')
                              ->orWhere('recipient_type', 'all_partners');
                        })->count();
                }
            @endphp
            @if ($unreadCommsCount > 0)
                <span class="px-1.5 py-0.5 text-[10px] font-bold bg-[#167C78] text-white rounded-full">
                    {{ $unreadCommsCount }}
                </span>
            @endif
        </a>
        <a href="{{ route('hospital.team.index') }}" 
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
