@props(['active' => 'dashboard'])

<aside class="w-64 flex-shrink-0 bg-white border-r border-[#D5DEDF] flex flex-col justify-between py-6 sticky top-14 h-[calc(100vh-3.5rem)] overflow-y-auto self-start">
    <nav class="space-y-1 px-4 text-xs font-semibold">
        <a href="{{ route('lab.dashboard') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'dashboard' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Intake & Processing Queue
        </a>
        <a href="{{ route('lab.reception.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'reception' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Specimen Reception
        </a>
        <a href="{{ route('lab.results.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'results' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Result Entry & Release
        </a>
        <a href="{{ route('lab.catalogue.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'catalogue' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Test Catalogue & Capacity
        </a>
        <a href="{{ route('lab.qualifications.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'qualifications' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Licensing & Qualifications
        </a>
        <a href="{{ route('lab.settlements.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'settlements' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Settlement Statements
        </a>
        <a href="{{ route('lab.team.index') }}" 
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
