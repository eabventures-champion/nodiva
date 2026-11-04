<x-layouts.workspace title="Authorising Pathologist Setup Required — NODIVA" :organization="$organization">
    <x-slot:sidebar>
        <x-lab-sidebar active="dashboard" />
    </x-slot:sidebar>

    <div class="max-w-4xl mx-auto space-y-6 py-4">

        <!-- Compliance Mandate Banner -->
        <div class="bg-white border-2 border-amber-400 p-6 space-y-4 shadow-sm">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-[#D5DEDF] pb-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-bold uppercase rounded-sm">
                            Mandatory Clinical Governance
                        </span>
                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase rounded-sm">
                            Account Approved
                        </span>
                    </div>
                    <h1 class="text-xl font-bold text-[#102A36]">Authorising Pathologist Provisioning Required</h1>
                    <p class="text-xs text-[#61727D]">
                        Your laboratory account for <strong class="text-[#102A36]">{{ $organization->name }}</strong> is approved. To ensure compliance with ISO 15189 (§5.8) and Allied Health Professions Council (AHPC) standards, an <strong>Authorising Pathologist</strong> must be provisioned before accessing the intake queues and test catalogue.
                    </p>
                </div>
            </div>

            <!-- Standard Governance Explanatory Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="p-4 bg-[#F5F3ED] border border-[#D5DEDF] space-y-1 rounded-sm">
                    <div class="font-bold text-[#102A36] uppercase text-[10px] text-[#167C78]">1. Dual-Control Protocol</div>
                    <p class="text-[#61727D] leading-relaxed text-[11px]">
                        Medical Laboratory Scientists enter quantitative findings. Consultant Pathologists independently correlate findings and issue release authorization.
                    </p>
                </div>
                <div class="p-4 bg-[#F5F3ED] border border-[#D5DEDF] space-y-1 rounded-sm">
                    <div class="font-bold text-[#102A36] uppercase text-[10px] text-[#167C78]">2. ISO 15189 Signatory</div>
                    <p class="text-[#61727D] leading-relaxed text-[11px]">
                        Referring hospitals, clinics, and doctors require verified pathologist credentials on every authorized diagnostic report.
                    </p>
                </div>
                <div class="p-4 bg-[#F5F3ED] border border-[#D5DEDF] space-y-1 rounded-sm">
                    <div class="font-bold text-[#102A36] uppercase text-[10px] text-[#167C78]">3. Instant Unlock</div>
                    <p class="text-[#61727D] leading-relaxed text-[11px]">
                        Once an Authorising Pathologist is created, all laboratory operational queues (specimen reception, intake, and results) become accessible immediately.
                    </p>
                </div>
            </div>
        </div>

        <!-- Provision Pathologist Form -->
        <div class="bg-white border border-[#D5DEDF] p-6 space-y-4 shadow-sm">
            <div class="border-b border-[#D5DEDF] pb-3">
                <h2 class="text-sm font-bold text-[#102A36] uppercase tracking-wider">Provision Authorising Pathologist Personnel</h2>
                <p class="text-xs text-[#61727D] mt-0.5">Enter the credentials for your laboratory's designated Consultant Pathologist or Authorising Medical Officer.</p>
            </div>

            @if ($errors->any())
                <div class="p-3 bg-red-50 border border-red-200 text-red-800 text-xs rounded-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('lab.pathologist.setup') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">
                            Pathologist Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="e.g. Dr. Owusu Bempah"
                               class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">
                            Official Institutional Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="pathologist@yourlab.com"
                               class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">
                            Clinical Title / Designation <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="job_title" value="{{ old('job_title', 'Consultant Pathologist, FRCPath — ISO 15189 Signatory') }}" required
                               class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none font-medium">
                    </div>
                    <div>
                        <label class="block font-bold text-[#102A36] mb-1">
                            Initial Login Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password" required minlength="8"
                               placeholder="Minimum 8 characters"
                               class="w-full border border-[#D5DEDF] p-2.5 focus:border-[#167C78] focus:outline-none">
                    </div>
                </div>

                <div class="p-3 bg-[#E8F0EF] border border-[#167C78]/20 text-[11px] text-[#167C78] flex items-center justify-between">
                    <div>
                        Assigned Role: <strong>Authorising Pathologist (Clinical Correlation & Release Protocol)</strong>
                    </div>
                    <span class="px-2 py-0.5 bg-[#167C78] text-white text-[9px] font-bold uppercase rounded-sm">
                        Release Privileges Granted
                    </span>
                </div>

                <div class="pt-3 border-t border-[#D5DEDF] flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                    <a href="{{ route('lab.team.index') }}" class="text-xs text-[#167C78] hover:underline font-semibold">
                        &larr; Manage Existing Team or Send Email Invitation
                    </a>
                    <button type="submit"
                            class="px-6 py-2.5 bg-[#167C78] hover:bg-[#136663] text-white font-bold uppercase tracking-wider transition shadow-sm inline-flex items-center gap-2">
                        <span>Provision Authorising Pathologist & Unlock Dashboard &rarr;</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.workspace>
