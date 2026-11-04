<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Finance\InvoiceService;
use App\Domain\Finance\Money;
use App\Domain\Network\MatchingEngine;
use App\Domain\Organizations\Actor;
use App\Domain\Organizations\Enums\OrganizationType;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Domain\Referrals\OrderWorkflow;
use App\Domain\Results\Enums\ResultStatus;
use App\Domain\Results\ResultWorkflow;
use App\Domain\Specimens\Enums\SpecimenStatus;
use App\Domain\Specimens\SpecimenCustody;
use App\Domain\Support\ReferenceGenerator;
use App\Models\DiagnosticResult;
use App\Models\DiagnosticResultItem;
use App\Models\DiagnosticSpecialty;
use App\Models\DiagnosticTest;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\ReferralOrder;
use App\Models\ReferralOrderItem;
use App\Models\SpecimenType;
use App\Models\TestCatalogueEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

class HospitalReferralSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Organization::where('name', 'Ridge Specialist Hospital')->first();
        $lab = Organization::where('name', 'SynLab Diagnostics West Africa')->first();
        $doctor = User::where('email', 'doctor@ridgespecialist.com')->first();
        $scientist = User::where('email', 'scientist@synlab-gh.example')->first();
        $pathologist = User::where('email', 'pathologist@synlab-gh.example')->first();

        if (! $hospital || ! $lab || ! $doctor) {
            return;
        }

        $doctorActor = Actor::for($doctor, $hospital);
        $scientistActor = Actor::for($scientist, $lab);
        $pathologistActor = Actor::for($pathologist, $lab);

        // Fetch specialties and specimen types
        $molBio = DiagnosticSpecialty::where('code', 'MOL_BIO')->first();
        $hema = DiagnosticSpecialty::where('code', 'HEMA')->first();
        $chem = DiagnosticSpecialty::where('code', 'CLIN_CHEM')->first();
        $micro = DiagnosticSpecialty::where('code', 'MICRO')->first();
        $pathology = DiagnosticSpecialty::where('code', 'HISTO_PATH')->first();

        $edtaBlood = SpecimenType::where('code', 'BLD_EDTA')->first();
        $serum = SpecimenType::where('code', 'SERUM')->first();
        $biopsy = SpecimenType::where('code', 'TISSUE_FFPE')->first();

        $sputum = SpecimenType::firstOrCreate(['code' => 'SPUTUM'], [
            'name' => 'Early Morning Sputum Container',
            'container' => 'Sterile wide-mouth specimen cup',
            'transport_requirements' => 'ambient',
            'stability_hours' => 24,
        ]);

        $cytologyVial = SpecimenType::firstOrCreate(['code' => 'CYTO_VIAL'], [
            'name' => 'Liquid-Based Cytology Preservative Vial',
            'container' => 'ThinPrep PreservCyt vial',
            'transport_requirements' => 'ambient',
            'stability_hours' => 336,
        ]);

        // Enrich diagnostic tests
        $tCbc = DiagnosticTest::firstOrCreate(['code' => 'CBC_DIFF'], [
            'name' => 'Full Blood Count (FBC) with 5-Part Differential',
            'diagnostic_specialty_id' => $hema->id,
            'specimen_type_id' => $edtaBlood->id,
            'loinc_code' => '57021-8',
            'description' => 'Complete hemogram: WBC, RBC, Hemoglobin, Hematocrit, Platelets, MCV, MCH, and differential.',
            'is_active' => true,
        ]);

        $tPsa = DiagnosticTest::firstOrCreate(['code' => 'PSA_TOTAL'], [
            'name' => 'Total Prostate-Specific Antigen (PSA) Quantitative',
            'diagnostic_specialty_id' => $chem->id,
            'specimen_type_id' => $serum->id,
            'loinc_code' => '2857-1',
            'description' => 'Chemiluminescent immunoassay for total serum PSA in prostate screening and monitoring.',
            'patient_preparation' => 'Avoid vigorous bicycle exercise and sexual activity for 48 hours prior to phlebotomy.',
            'is_active' => true,
        ]);

        $tTb = DiagnosticTest::firstOrCreate(['code' => 'TB_GENEXPERT'], [
            'name' => 'MTB/RIF Ultra Real-Time PCR (GeneXpert)',
            'diagnostic_specialty_id' => $micro->id,
            'specimen_type_id' => $sputum->id,
            'loinc_code' => '89883-3',
            'description' => 'Automated cartridge-based nucleic acid amplification test for Mycobacterium tuberculosis and Rifampicin resistance.',
            'requires_clinical_notes' => true,
            'is_active' => true,
        ]);

        $tPap = DiagnosticTest::firstOrCreate(['code' => 'CYTO_PAP_LBC'], [
            'name' => 'Liquid-Based Cervical Cytology (ThinPrep Pap)',
            'diagnostic_specialty_id' => $pathology->id,
            'specimen_type_id' => $cytologyVial->id,
            'loinc_code' => '10524-7',
            'description' => 'Automated ThinPrep processing with Bethesda system cytopathological evaluation.',
            'is_active' => true,
        ]);

        // SynLab catalogue entries
        $extraTests = [
            ['test' => $tCbc, 'code' => 'SL-HEM-002', 'price' => '85.00', 'tat' => 6],
            ['test' => $tPsa, 'code' => 'SL-CHM-045', 'price' => '160.00', 'tat' => 12],
            ['test' => $tTb, 'code' => 'SL-MIC-019', 'price' => '280.00', 'tat' => 8],
            ['test' => $tPap, 'code' => 'SL-HIS-022', 'price' => '250.00', 'tat' => 48],
        ];

        foreach ($extraTests as $item) {
            TestCatalogueEntry::firstOrCreate([
                'organization_id' => $lab->id,
                'diagnostic_test_id' => $item['test']->id,
            ], [
                'lab_test_code' => $item['code'],
                'price' => $item['price'],
                'currency' => 'GHS',
                'turnaround_hours' => $item['tat'],
                'is_available' => true,
            ]);
        }

        // Seed realistic Patient Kofi Mensah if not exists
        $mrn = 'MRN-2026-1044';
        $mrnHash = Patient::mrnHash($hospital->uuid, $mrn);
        $patient = Patient::firstOrNew(['organization_id' => $hospital->id, 'mrn_hash' => $mrnHash]);
        if (! $patient->exists) {
            $patient->medical_record_number = $mrn;
            $patient->first_name = 'Kwame';
            $patient->last_name = 'Ofori-Atta';
            $patient->date_of_birth = '1984-11-23';
            $patient->sex = 'male';
            $patient->phone = '+233244123456';
            $patient->reference = ReferenceGenerator::make('PT', 'patients', 'reference');
            $patient->created_by = $doctor->id;
            $patient->save();

            PatientConsent::create([
                'patient_id' => $patient->id,
                'organization_id' => $hospital->id,
                'purpose' => 'external_referral_testing',
                'method' => 'written',
                'granted_at' => now(),
                'notes' => 'Patient signed standard referral consent form for diagnostic processing.',
                'recorded_by' => $doctor->id,
            ]);
        }

        // Create 1 completed order with results & invoice
        if (ReferralOrder::where('requesting_organization_id', $hospital->id)->count() === 0) {
            $hbvTest = DiagnosticTest::where('code', 'HBV_VIRAL_LOAD')->first();
            $order = new ReferralOrder();
            $order->reference = ReferenceGenerator::make('RF', 'referral_orders', 'reference');
            $order->requesting_organization_id = $hospital->id;
            $order->patient_id = $patient->id;
            $order->created_by = $doctor->id;
            $order->status = OrderStatus::Draft;
            $order->priority = 'routine';
            $order->collection_method = 'courier';
            $order->clinical_indication = 'Monitoring chronic HBV on entecavir therapy';
            $order->currency = 'GHS';
            $order->save();

            ReferralOrderItem::create([
                'referral_order_id' => $order->id,
                'diagnostic_test_id' => $hbvTest->id,
            ]);

            $orderWorkflow = app(OrderWorkflow::class);
            $specimenCustody = app(SpecimenCustody::class);

            $order = $orderWorkflow->submit($order, $doctorActor);
            $order = $orderWorkflow->assignLaboratory($order, $lab, 'recommended', ['ISO 15189 Accredited', 'Fastest viral load TAT in Accra'], null, $doctorActor);
            $order = $orderWorkflow->acceptByLaboratory($order, $scientistActor);

            // Specimen collection & dispatch
            $specimen = $specimenCustody->register($order, $serum->id, $doctorActor);
            $specimenCustody->recordCollection($specimen, $doctorActor, 'Ridge Central Phlebotomy');
            $order = $order->fresh();
            $specimenCustody->dispatch($order, [
                'carrier_name' => 'Medical Express Courier (Ghana)',
                'tracking_reference' => 'MX-GH-55421',
                'pickup_location' => 'Ridge Hospital Central Handover',
            ], $doctorActor);
            $order = $order->fresh();

            // Lab receipt & processing
            $specimenCustody->recordReceipt($specimen->fresh(), $scientistActor, 'SynLab Intake Bay');
            $order = $order->fresh();
            $orderWorkflow->transition($order, OrderStatus::Processing, $scientistActor, 'Specimen placed on Abbott m2000sp/rt PCR system');
            $order->save();

            // Result entry & authorization
            $resultWorkflow = app(ResultWorkflow::class);
            $result = $resultWorkflow->enterResult($order, [
                'interpretation' => 'Viral load low, showing excellent virological response to antiviral regimen. Continue current therapy.',
                'items' => [
                    [
                        'analyte' => 'Hepatitis B Virus (HBV) DNA Quantitative',
                        'loinc_code' => '42595-9',
                        'value' => '28.5',
                        'unit' => 'IU/mL',
                        'reference_range' => '0.00 - 20.00',
                        'flag' => 'normal',
                    ],
                ],
            ], $scientistActor);

            $resultWorkflow->submitForReview($result, $scientistActor);
            $resultWorkflow->releaseResult($result, $pathologistActor, 'Approved for release by Consultant Pathologist');

            $order = $order->fresh();
            $orderWorkflow->transition($order, OrderStatus::Completed, $pathologistActor, 'Diagnostic report authorized and completed');
            $order->save();

            // Invoicing
            $invoiceService = app(InvoiceService::class);
            $invoiceService->createForOrder($order, $doctorActor);
        }
    }
}
