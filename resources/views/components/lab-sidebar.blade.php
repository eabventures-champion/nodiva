@props(['active' => 'dashboard', 'organization' => null])

@php
    $labOrg = $organization ?? auth()->user()?->currentMembership?->organization ?? auth()->user()?->activeMemberships()->first()?->organization;
    $hasMissingLicence = $labOrg ? $labOrg->hasMissingQualifications() : false;
    $missingCount = $labOrg ? $labOrg->missingQualificationsCount() : 0;
@endphp

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

        <!-- Laboratory Licensing & Qualifications with Beeping Alert Feature -->
        @if ($hasMissingLicence)
            <div x-data="{
                audioMuted: localStorage.getItem('nodiva_lab_beep_muted') === 'true',
                init() {
                    // Soft alert double beep on mount if unmuted
                    if (!this.audioMuted) {
                        setTimeout(() => this.playBeep(), 800);
                    }
                },
                playBeep() {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const playTone = (freq, start, duration) => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(freq, ctx.currentTime + start);
                            gain.gain.setValueAtTime(0.12, ctx.currentTime + start);
                            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + duration);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(ctx.currentTime + start);
                            osc.stop(ctx.currentTime + start + duration);
                        };
                        playTone(880, 0.05, 0.12);
                        playTone(1046.5, 0.22, 0.18);
                    } catch (e) {
                        // User interaction may be required by some browsers before audio playback
                    }
                },
                toggleMute() {
                    this.audioMuted = !this.audioMuted;
                    localStorage.setItem('nodiva_lab_beep_muted', this.audioMuted);
                }
            }" class="space-y-1">
                <a href="{{ route('lab.qualifications.index') }}" 
                   title="Action Required: Mandatory accreditation & licensing documents have not been uploaded!"
                   class="flex items-center justify-between px-3 py-2 rounded-sm border transition shadow-2xs {{ $active === 'qualifications' ? 'bg-[#167C78] text-white border-[#167C78] font-bold' : 'bg-rose-50/90 text-[#B42332] border-rose-300 hover:bg-rose-100 font-bold' }}">
                    <div class="flex items-center gap-2 min-w-0">
                        <!-- Pulsing / Beeping Radar Beacon Indicator -->
                        <span class="relative flex h-2.5 w-2.5 shrink-0">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-500 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#B42332]"></span>
                        </span>
                        <span class="truncate">Licensing & Qualifications</span>
                    </div>

                    <span class="px-1.5 py-0.5 text-[9px] font-bold bg-[#B42332] text-white rounded-full uppercase tracking-wider animate-pulse flex items-center gap-1 shadow-2xs shrink-0">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>MISSING</span>
                    </span>
                </a>

                <!-- Beep alert sound indicator & mute control -->
                <div class="flex items-center justify-between px-2.5 py-1 bg-rose-100/70 rounded-xs text-[10px] text-rose-900 border border-rose-200">
                    <button type="button" @click="playBeep()" class="flex items-center gap-1 hover:underline cursor-pointer">
                        <svg class="w-3 h-3 text-rose-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                        <span class="font-bold">Beep Alert Active</span>
                    </button>
                    <button type="button" @click="toggleMute()" class="text-rose-700 hover:text-rose-950 underline text-[9px] cursor-pointer" x-text="audioMuted ? 'Unmute' : 'Mute'"></button>
                </div>
            </div>
        @else
            <a href="{{ route('lab.qualifications.index') }}" 
               class="block px-3 py-2 rounded-sm {{ $active === 'qualifications' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
                Licensing & Qualifications
            </a>
        @endif

        <a href="{{ route('lab.settlements.index') }}" 
           class="block px-3 py-2 rounded-sm {{ $active === 'settlements' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            Settlement Statements
        </a>

        <a href="{{ route('lab.communications.index') }}" 
           class="flex items-center justify-between px-3 py-2 rounded-sm {{ $active === 'communications' ? 'bg-[#E8F0EF] text-[#167C78] font-bold' : 'text-[#61727D] hover:bg-[#F5F3ED] hover:text-[#172A35]' }}">
            <span>Platform Communications</span>
            @php
                $labCommsCount = 0;
                if ($labOrg) {
                    $labCommsCount = \App\Models\CommunicationThread::where('status', 'open')
                        ->where(function ($q) use ($labOrg) {
                            $q->where('recipient_organization_id', $labOrg->id)
                              ->orWhere('sender_organization_id', $labOrg->id)
                              ->orWhere('recipient_type', 'all_laboratories')
                              ->orWhere('recipient_type', 'all_partners');
                        })->count();
                }
            @endphp
            @if ($labCommsCount > 0)
                <span class="px-1.5 py-0.5 text-[10px] font-bold bg-[#167C78] text-white rounded-full">
                    {{ $labCommsCount }}
                </span>
            @endif
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
