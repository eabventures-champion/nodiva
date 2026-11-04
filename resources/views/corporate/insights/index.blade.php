<x-layouts.workspace title="Aggregate Health Insights — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-corporate-sidebar active="insights" />
    </x-slot:sidebar>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#102A36]">Aggregate Population Health Insights</h1>
            <p class="text-xs text-[#61727D]">Anonymized workforce health metrics, participation rates, and occupational wellness analytics.</p>
        </div>
        <div class="text-xs text-[#61727D] font-mono">
            Compliance Standard: <strong>Ghana Data Protection Act 2012 (Act 843)</strong>
        </div>
    </div>

    <!-- Small-Cell Suppression Privacy Safeguard Notice -->
    <div class="p-4 bg-[#E8F0EF] border border-[#167C78]/30 flex items-start space-x-3 text-xs text-[#172A35]">
        <svg class="w-5 h-5 text-[#167C78] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <div>
            <span class="font-bold block text-[#102A36]">Differential Privacy & Small-Cell Suppression Safeguard</span>
            To preserve individual worker confidentiality, all aggregate reports enforce small-cell suppression: groups or categories with fewer than 5 individuals are suppressed. Employers are never presented with individualized clinical results.
        </div>
    </div>

    <!-- Metric Stat Boxes -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#61727D] uppercase tracking-wider">Total Workforce Roster</div>
            <div class="text-2xl font-bold text-[#102A36] mt-1">{{ $totalEmployees }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#167C78] uppercase tracking-wider">Program Participants</div>
            <div class="text-2xl font-bold text-[#167C78] mt-1">{{ $totalParticipants }}</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#247451] uppercase tracking-wider">Workforce Participation Rate</div>
            <div class="text-2xl font-bold text-[#247451] mt-1">{{ $participationRate }}%</div>
        </div>
        <div class="bg-white p-4 border border-[#D5DEDF]">
            <div class="text-[11px] font-bold text-[#94611A] uppercase tracking-wider">Clinical Completion Rate</div>
            <div class="text-2xl font-bold text-[#94611A] mt-1">{{ $completionRate }}%</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Departmental Engagement Breakdown -->
        <div class="bg-white border border-[#D5DEDF]">
            <div class="px-6 py-4 border-b border-[#D5DEDF] flex justify-between items-center">
                <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider">Departmental Participation Breakdown</h2>
                <span class="text-[11px] text-[#61727D]">Anonymized</span>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="bg-[#F5F3ED] border-b border-[#D5DEDF] text-[#61727D] uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Department / Division</th>
                        <th class="px-6 py-3 text-right">Roster Count</th>
                        <th class="px-6 py-3 text-right">Privacy Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D5DEDF]">
                    @forelse ($departmentStats as $dept)
                        <tr class="hover:bg-[#E8F0EF]/40 transition">
                            <td class="px-6 py-3.5 font-bold text-[#102A36]">{{ $dept['department'] }}</td>
                            <td class="px-6 py-3.5 text-right font-mono font-bold text-[#102A36]">
                                {{ $dept['count'] }}
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                @if ($dept['is_suppressed'])
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-amber-100 text-amber-800">
                                        Suppressed (&lt; 5)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-sm bg-emerald-100 text-emerald-800">
                                        Audited
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-6 text-center text-[#61727D]">
                                No departmental records available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Population Risk Indices Summary -->
        <div class="bg-white border border-[#D5DEDF] p-6 space-y-5">
            <h2 class="text-xs font-bold text-[#102A36] uppercase tracking-wider border-b border-[#D5DEDF] pb-3">
                Population Health Risk Stratification
            </h2>

            <p class="text-xs text-[#61727D] leading-relaxed">
                Overall health indices aggregated across completed diagnostic profiles without exposing individual identities.
            </p>

            <div class="space-y-4 text-xs">
                <div>
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-[#102A36]">Cardio-Metabolic Risk Index</span>
                        <span class="text-[#247451]">Optimal / Low Risk (74%)</span>
                    </div>
                    <div class="w-full bg-[#E8F0EF] h-2 rounded-sm overflow-hidden">
                        <div class="bg-[#247451] h-2 w-3/4"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-[#102A36]">Occupational Fitness Clearance</span>
                        <span class="text-[#167C78]">Cleared Fit for Duty (92%)</span>
                    </div>
                    <div class="w-full bg-[#E8F0EF] h-2 rounded-sm overflow-hidden">
                        <div class="bg-[#167C78] h-2 w-11/12"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-[#102A36]">Viral Immunity / Vaccination Rate</span>
                        <span class="text-[#94611A]">Follow-up Advised (18%)</span>
                    </div>
                    <div class="w-full bg-[#E8F0EF] h-2 rounded-sm overflow-hidden">
                        <div class="bg-[#94611A] h-2 w-1/5"></div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-[#D5DEDF] text-[11px] text-[#61727D]">
                Detailed occupational physician consultation reports are provided confidentially directly to the employee by the fulfilling laboratory.
            </div>
        </div>
    </div>
</x-layouts.workspace>
