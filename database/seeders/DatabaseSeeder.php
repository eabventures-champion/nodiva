<?php

namespace Database\Seeders;

use App\Domain\Organizations\Enums\OrganizationStatus;
use App\Domain\Organizations\Enums\OrganizationType;
use App\Domain\Organizations\PermissionCatalogue;
use App\Models\DiagnosticSpecialty;
use App\Models\DiagnosticTest;
use App\Models\LaboratoryProfile;
use App\Models\LaboratoryQualification;
use App\Models\Organization;
use App\Models\OrganizationBranch;
use App\Models\OrganizationMembership;
use App\Models\Permission;
use App\Models\PlatformFeeRule;
use App\Models\Role;
use App\Models\ServiceRegion;
use App\Models\SpecimenType;
use App\Models\TestCatalogueEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Permissions & Roles
        $permissions = [];
        foreach (PermissionCatalogue::permissions() as $key => [$name, $group, $sensitive]) {
            $permissions[$key] = Permission::firstOrCreate(['key' => $key], [
                'name' => $name,
                'group' => $group,
                'is_sensitive' => $sensitive,
            ]);
        }

        $roles = [];
        foreach (PermissionCatalogue::roles() as $key => $meta) {
            $role = Role::firstOrCreate(['key' => $key], [
                'name' => $meta['name'],
                'organization_type' => $meta['type'],
                'is_admin' => $meta['admin'],
                'description' => $meta['description'],
            ]);

            $permIds = [];
            foreach ($meta['permissions'] as $pKey) {
                if (isset($permissions[$pKey])) {
                    $permIds[] = $permissions[$pKey]->id;
                }
            }
            $role->permissions()->sync($permIds);
            $roles[$key] = $role;
        }

        // 2. Service Regions (Ghana initial launch geography)
        $accra = ServiceRegion::firstOrCreate(['code' => 'GH-AA'], ['name' => 'Greater Accra Region', 'country_code' => 'GH', 'is_launch_active' => true]);
        $ashanti = ServiceRegion::firstOrCreate(['code' => 'GH-AH'], ['name' => 'Ashanti Region', 'country_code' => 'GH', 'is_launch_active' => true]);
        $western = ServiceRegion::firstOrCreate(['code' => 'GH-WP'], ['name' => 'Western Region', 'country_code' => 'GH', 'is_launch_active' => true]);

        // 3. Platform Fee Rule (12% illustrative GMV fee)
        PlatformFeeRule::firstOrCreate(['scope' => 'global', 'name' => 'Default Platform Fee'], [
            'rate_percent' => '12.00',
            'flat_fee' => '0.00',
            'currency' => 'GHS',
            'fee_bearer' => 'laboratory',
            'priority' => 10,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // 4. Diagnostic Specialties & Specimen Types
        $molBio = DiagnosticSpecialty::firstOrCreate(['code' => 'MOL_BIO'], ['name' => 'Molecular Diagnostics & Genetics', 'required_qualification_type' => 'accreditation']);
        $pathology = DiagnosticSpecialty::firstOrCreate(['code' => 'HISTO_PATH'], ['name' => 'Histopathology & Cytology', 'required_qualification_type' => 'accreditation']);
        $microbio = DiagnosticSpecialty::firstOrCreate(['code' => 'MICRO'], ['name' => 'Clinical Microbiology & Serology']);
        $chem = DiagnosticSpecialty::firstOrCreate(['code' => 'CLIN_CHEM'], ['name' => 'Clinical Chemistry & Endocrinology']);
        $hema = DiagnosticSpecialty::firstOrCreate(['code' => 'HEMA'], ['name' => 'Hematology & Coagulation']);

        $edtaBlood = SpecimenType::firstOrCreate(['code' => 'BLD_EDTA'], ['name' => 'Whole Blood (EDTA tube)', 'container' => 'Lavender top', 'transport_requirements' => 'ambient', 'stability_hours' => 48]);
        $serum = SpecimenType::firstOrCreate(['code' => 'SERUM'], ['name' => 'Serum (SST tube)', 'container' => 'Gold top', 'transport_requirements' => 'refrigerated', 'stability_hours' => 72]);
        $biopsy = SpecimenType::firstOrCreate(['code' => 'TISSUE_FFPE'], ['name' => 'Tissue Biopsy (10% Formalin)', 'container' => 'Formalin container', 'transport_requirements' => 'ambient', 'stability_hours' => 168]);

        // 5. Standard Diagnostic Tests
        $t1 = DiagnosticTest::firstOrCreate(['code' => 'HBV_VIRAL_LOAD'], [
            'name' => 'Hepatitis B Virus (HBV) Quantitative DNA PCR',
            'diagnostic_specialty_id' => $molBio->id,
            'specimen_type_id' => $serum->id,
            'loinc_code' => '42595-9',
            'description' => 'Real-time quantitative PCR for HBV viral load monitoring.',
            'requires_clinical_notes' => true,
            'is_active' => true,
        ]);

        $t2 = DiagnosticTest::firstOrCreate(['code' => 'HISTO_BIOPSY_SML'], [
            'name' => 'Histopathological Examination of Small Biopsy Specimen',
            'diagnostic_specialty_id' => $pathology->id,
            'specimen_type_id' => $biopsy->id,
            'loinc_code' => '22637-3',
            'description' => 'Gross and microscopic histopathology evaluation of biopsy tissue.',
            'requires_clinical_notes' => true,
            'is_active' => true,
        ]);

        $t3 = DiagnosticTest::firstOrCreate(['code' => 'LIPID_PANEL'], [
            'name' => 'Comprehensive Lipid Profile Panel',
            'diagnostic_specialty_id' => $chem->id,
            'specimen_type_id' => $serum->id,
            'loinc_code' => '24331-1',
            'description' => 'Total Cholesterol, HDL, LDL, Triglycerides, Non-HDL, Risk Ratio.',
            'patient_preparation' => '10-12 hours overnight fasting recommended.',
            'is_active' => true,
        ]);

        // 6. Seed Organizations & Users
        $defaultPassword = Hash::make('NodivaSecure2026!');

        // Platform Operations
        $platformOrg = Organization::firstOrCreate(['name' => 'NODIVA Operations Network'], [
            'type' => OrganizationType::Platform,
            'status' => OrganizationStatus::Active,
            'email' => 'ops@nodiva.com',
            'phone' => '+233302000001',
            'city' => 'Accra',
            'service_region_id' => $accra->id,
            'currency' => 'GHS',
            'country_code' => 'GH',
        ]);

        $adminUser = User::firstOrCreate(['email' => 'admin@nodiva.com'], [
            'name' => 'Dr. Kojo Mensah',
            'password' => $defaultPassword,
            'job_title' => 'Chief Operations Officer',
        ]);

        $mAdmin = OrganizationMembership::firstOrCreate([
            'user_id' => $adminUser->id,
            'organization_id' => $platformOrg->id,
        ], ['role_id' => $roles['platform_admin']->id]);
        $adminUser->current_membership_id = $mAdmin->id;
        $adminUser->save();

        // Referring Hospital: Ridge Specialist Hospital
        $hospital = Organization::firstOrCreate(['name' => 'Ridge Specialist Hospital'], [
            'type' => OrganizationType::Hospital,
            'status' => OrganizationStatus::Active,
            'registration_number' => 'HEFRA/HOSP/2026/041',
            'email' => 'referrals@ridgespecialist.com',
            'phone' => '+233302000002',
            'city' => 'Accra',
            'service_region_id' => $accra->id,
            'currency' => 'GHS',
            'country_code' => 'GH',
        ]);

        $hBranch = OrganizationBranch::firstOrCreate([
            'organization_id' => $hospital->id,
            'name' => 'Main Referral Wing',
        ], [
            'code' => 'RIDGE-MAIN',
            'city' => 'Accra',
            'service_region_id' => $accra->id,
            'is_primary' => true,
        ]);

        $hospAdmin = User::firstOrCreate(['email' => 'admin@ridgespecialist.com'], [
            'name' => 'Dr. Anthony Boateng',
            'password' => $defaultPassword,
            'job_title' => 'Chief Medical Officer & Hospital Administrator',
        ]);
        $mHospAdmin = OrganizationMembership::firstOrCreate([
            'user_id' => $hospAdmin->id,
            'organization_id' => $hospital->id,
        ], ['role_id' => $roles['hospital_admin']->id]);
        $hospAdmin->current_membership_id = $mHospAdmin->id;
        $hospAdmin->save();

        $docUser = User::firstOrCreate(['email' => 'doctor@ridgespecialist.com'], [
            'name' => 'Dr. Ama Serwaa',
            'password' => $defaultPassword,
            'job_title' => 'Senior Consultant Physician',
        ]);

        $mDoc = OrganizationMembership::firstOrCreate([
            'user_id' => $docUser->id,
            'organization_id' => $hospital->id,
        ], ['role_id' => $roles['hospital_clinician']->id]);
        $docUser->current_membership_id = $mDoc->id;
        $docUser->save();

        // Laboratory Partner: SynLab Medical Ghana
        $lab = Organization::firstOrCreate(['name' => 'SynLab Diagnostics West Africa'], [
            'type' => OrganizationType::Laboratory,
            'status' => OrganizationStatus::Active,
            'registration_number' => 'AHPC/LAB/2026/089',
            'email' => 'intake@synlab-gh.example',
            'phone' => '+233302000003',
            'city' => 'Accra',
            'service_region_id' => $accra->id,
            'currency' => 'GHS',
            'country_code' => 'GH',
        ]);

        $lab->serviceRegions()->sync([$accra->id, $ashanti->id]);

        $profile = LaboratoryProfile::firstOrCreate(['organization_id' => $lab->id], [
            'description' => 'ISO 15189 Accredited Tertiary Referral Reference Laboratory.',
            'daily_capacity' => 1500,
            'is_accepting_orders' => true,
            'offers_collection' => true,
            'reliability_score' => '98.50',
        ]);

        // Qualifications
        LaboratoryQualification::firstOrCreate(['reference_number' => 'AHPC/LAB/2026/089'], [
            'organization_id' => $lab->id,
            'type' => 'operating_license',
            'issuing_body' => 'Allied Health Professions Council Ghana',
            'scope' => 'Comprehensive Clinical Laboratory Practice',
            'issued_on' => '2026-01-01',
            'expires_on' => '2027-12-31',
            'status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $adminUser->id,
        ]);

        LaboratoryQualification::firstOrCreate(['reference_number' => 'ISO-15189-GH-044'], [
            'organization_id' => $lab->id,
            'type' => 'accreditation',
            'issuing_body' => 'Southern African Development Community Accreditation Service (SADCAS)',
            'scope' => 'Molecular Diagnostics, Histopathology, Clinical Chemistry',
            'issued_on' => '2025-06-01',
            'expires_on' => '2028-05-31',
            'status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $adminUser->id,
        ]);

        // Test Catalogue Entries for Lab
        TestCatalogueEntry::firstOrCreate([
            'organization_id' => $lab->id,
            'diagnostic_test_id' => $t1->id,
        ], [
            'lab_test_code' => 'SL-MOL-001',
            'price' => '450.00',
            'currency' => 'GHS',
            'turnaround_hours' => 24,
            'is_available' => true,
        ]);

        TestCatalogueEntry::firstOrCreate([
            'organization_id' => $lab->id,
            'diagnostic_test_id' => $t2->id,
        ], [
            'lab_test_code' => 'SL-HIS-008',
            'price' => '380.00',
            'currency' => 'GHS',
            'turnaround_hours' => 48,
            'is_available' => true,
        ]);

        TestCatalogueEntry::firstOrCreate([
            'organization_id' => $lab->id,
            'diagnostic_test_id' => $t3->id,
        ], [
            'lab_test_code' => 'SL-CHM-012',
            'price' => '120.00',
            'currency' => 'GHS',
            'turnaround_hours' => 12,
            'is_available' => true,
        ]);

        // Lab Personnel: Administrator, Scientist & Pathologist
        $labAdminUser = User::firstOrCreate(['email' => 'admin@synlab-gh.example'], [
            'name' => 'Dr. Irene Mensah',
            'password' => $defaultPassword,
            'job_title' => 'Laboratory Administrator & General Manager',
        ]);
        $mLabAdmin = OrganizationMembership::firstOrCreate([
            'user_id' => $labAdminUser->id,
            'organization_id' => $lab->id,
        ], ['role_id' => $roles['lab_admin']->id]);
        $labAdminUser->current_membership_id = $mLabAdmin->id;
        $labAdminUser->save();

        $scientistUser = User::firstOrCreate(['email' => 'scientist@synlab-gh.example'], [
            'name' => 'Emmanuel Osei, MLS',
            'password' => $defaultPassword,
            'job_title' => 'Senior Medical Laboratory Scientist',
        ]);
        $mScientist = OrganizationMembership::firstOrCreate([
            'user_id' => $scientistUser->id,
            'organization_id' => $lab->id,
        ], ['role_id' => $roles['lab_scientist']->id]);
        $scientistUser->current_membership_id = $mScientist->id;
        $scientistUser->save();

        $pathologistUser = User::firstOrCreate(['email' => 'pathologist@synlab-gh.example'], [
            'name' => 'Prof. Kwame Boateng, FRCPath',
            'password' => $defaultPassword,
            'job_title' => 'Consultant Pathologist & Clinical Director',
        ]);
        $mPathologist = OrganizationMembership::firstOrCreate([
            'user_id' => $pathologistUser->id,
            'organization_id' => $lab->id,
        ], ['role_id' => $roles['lab_pathologist']->id]);
        $pathologistUser->current_membership_id = $mPathologist->id;
        $pathologistUser->save();

        // 7. Corporate Organization
        $corporateOrg = Organization::firstOrCreate(['name' => 'Tullow Oil Ghana Ltd'], [
            'type' => OrganizationType::Corporate,
            'status' => OrganizationStatus::Active,
            'registration_number' => 'RGD-CS-2026-9901',
            'email' => 'wellness@tullow.example',
            'phone' => '+233302000004',
            'city' => 'Accra',
            'service_region_id' => $accra->id,
            'currency' => 'GHS',
            'country_code' => 'GH',
        ]);

        $hrUser = User::firstOrCreate(['email' => 'hr@tullow.example'], [
            'name' => 'Abena Nyarko',
            'password' => $defaultPassword,
            'job_title' => 'Occupational Health & Wellness Manager',
        ]);
        $mHr = OrganizationMembership::firstOrCreate([
            'user_id' => $hrUser->id,
            'organization_id' => $corporateOrg->id,
        ], ['role_id' => $roles['corporate_admin']->id]);
        $hrUser->current_membership_id = $mHr->id;
        $hrUser->save();

        if (! app()->environment('testing')) {
            $this->call(HospitalReferralSeeder::class);
            $this->call(CorporateSeeder::class);
        }
    }
}
