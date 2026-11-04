<?php

namespace Tests\Feature;

use App\Domain\Finance\FinancialCalculator;
use App\Domain\Finance\InvoiceService;
use App\Domain\Finance\Money;
use App\Domain\Finance\ReconciliationEngine;
use App\Domain\Network\MatchingEngine;
use App\Domain\Organizations\Actor;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Domain\Referrals\OrderWorkflow;
use App\Domain\Results\Enums\ResultStatus;
use App\Domain\Results\ResultWorkflow;
use App\Domain\Specimens\Enums\SpecimenStatus;
use App\Domain\Specimens\SpecimenCustody;
use App\Domain\Support\ReferenceGenerator;
use App\Models\DiagnosticResult;
use App\Models\DiagnosticTest;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\ReferralOrder;
use App\Models\ReferralOrderItem;
use App\Models\SpecimenType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndReferralWorkflowTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Acceptance Test Milestone 1:
     * Complete one referral from creation through matching, acceptance, specimen custody,
     * analytical processing, result entry, review & release, invoicing, payment allocation,
     * and reconciliation run.
     */
    public function test_complete_diagnostic_network_referral_vertical_slice(): void
    {
        // 1. Setup seed data
        $this->seed(DatabaseSeeder::class);

        // Fetch seeded users and orgs
        $hospital = Organization::where('name', 'Ridge Specialist Hospital')->firstOrFail();
        $lab = Organization::where('name', 'SynLab Diagnostics West Africa')->firstOrFail();
        $opsOrg = Organization::where('name', 'NODIVA Operations Network')->firstOrFail();

        $doctor = User::where('email', 'doctor@ridgespecialist.com')->firstOrFail();
        $scientist = User::where('email', 'scientist@synlab-gh.example')->firstOrFail();
        $pathologist = User::where('email', 'pathologist@synlab-gh.example')->firstOrFail();
        $opsAdmin = User::where('email', 'admin@nodiva.com')->firstOrFail();

        $doctorActor = Actor::for($doctor, $hospital);
        $scientistActor = Actor::for($scientist, $lab);
        $pathologistActor = Actor::for($pathologist, $lab);
        $opsActor = Actor::for($opsAdmin, $opsOrg);

        // 2. Hospital creates Patient & Records Consent
        $patient = new Patient([
            'medical_record_number' => 'MRN-2026-9912',
            'first_name' => 'Kofi',
            'last_name' => 'Mensah',
            'date_of_birth' => '1988-04-12',
            'sex' => 'male',
            'phone' => '+233240001122',
        ]);
        $patient->organization_id = $hospital->id;
        $patient->mrn_hash = Patient::mrnHash($hospital->uuid, 'MRN-2026-9912');
        $patient->reference = ReferenceGenerator::make('PT', 'patients', 'reference');
        $patient->created_by = $doctor->id;
        $patient->save();

        PatientConsent::create([
            'patient_id' => $patient->id,
            'organization_id' => $hospital->id,
            'purpose' => 'external_referral_testing',
            'method' => 'written',
            'granted_at' => now(),
            'notes' => 'Patient signed standard referral consent form',
            'recorded_by' => $doctor->id,
        ]);

        $this->assertTrue($patient->hasActiveConsent('external_referral_testing'));

        // 3. Create Draft Referral Order
        $hbvTest = DiagnosticTest::where('code', 'HBV_VIRAL_LOAD')->firstOrFail();

        $order = new ReferralOrder();
        $order->reference = ReferenceGenerator::make('RF', 'referral_orders', 'reference');
        $order->requesting_organization_id = $hospital->id;
        $order->patient_id = $patient->id;
        $order->created_by = $doctor->id;
        $order->status = OrderStatus::Draft;
        $order->priority = 'routine';
        $order->collection_method = 'courier';
        $order->clinical_indication = 'Chronic Hepatitis B monitoring';
        $order->currency = 'GHS';
        $order->save();

        ReferralOrderItem::create([
            'referral_order_id' => $order->id,
            'diagnostic_test_id' => $hbvTest->id,
        ]);

        // 4. Matching Engine evaluates available labs
        $matching = app(MatchingEngine::class);
        $matches = $matching->match($order);

        $this->assertNotEmpty($matches);
        $topMatch = $matches->first();
        $this->assertEquals($lab->id, $topMatch['laboratory']->id);
        $this->assertTrue($topMatch['eligible']);
        $this->assertGreaterThan(80, $topMatch['score']);

        // 5. Submit Order & Assign Recommended Lab
        $workflow = app(OrderWorkflow::class);
        $order = $workflow->submit($order, $doctorActor);
        $this->assertEquals(OrderStatus::Matching, $order->status);

        $order = $workflow->assignLaboratory(
            $order,
            $lab,
            'recommended',
            $topMatch['rationale'],
            null,
            $doctorActor
        );
        $this->assertEquals(OrderStatus::AwaitingLabAcceptance, $order->status);
        $this->assertEquals($lab->id, $order->laboratory_id);

        // 6. Laboratory Accepts Order
        $order = $workflow->acceptByLaboratory($order, $scientistActor);
        $this->assertEquals(OrderStatus::CollectionScheduled, $order->status);

        // 7. Specimen Collection & Custody Tracking
        $specimenCustody = app(SpecimenCustody::class);
        $specimen = $specimenCustody->register($order, $hbvTest->specimen_type_id, $doctorActor);
        $this->assertEquals(SpecimenStatus::PendingCollection, $specimen->status);
        $this->assertTrue(ReferenceGenerator::isValidSpecimen($specimen->identifier));

        // Hospital records collection
        $specimenCustody->recordCollection($specimen, $doctorActor, 'Ridge Hospital Phlebotomy');
        $this->assertEquals(SpecimenStatus::Collected, $specimen->fresh()->status);
        $order = $order->fresh();
        $this->assertEquals(OrderStatus::SpecimenCollected, $order->status);

        // Dispatch with courier
        $specimenCustody->dispatch($order, [
            'type' => 'courier',
            'carrier_name' => 'Medical Express Courier',
            'tracking_reference' => 'MX-GH-78901',
            'pickup_location' => 'Ridge Central Lab',
        ], $doctorActor);
        $specimen = $specimen->fresh();
        $this->assertEquals(SpecimenStatus::InTransit, $specimen->status);
        $this->assertEquals(OrderStatus::InTransit, $order->fresh()->status);

        // Laboratory receives specimen
        $specimenCustody->recordReceipt($specimen, $scientistActor, 'SynLab Intake Bay 2');
        $this->assertEquals(SpecimenStatus::Received, $specimen->fresh()->status);
        $this->assertEquals(OrderStatus::SpecimenReceived, $order->fresh()->status);

        // Laboratory accepts specimen for processing
        $specimenCustody->acceptSpecimen($specimen, $scientistActor);
        $this->assertEquals(SpecimenStatus::Accepted, $specimen->fresh()->status);
        $this->assertEquals(OrderStatus::Processing, $order->fresh()->status);

        // 8. Result Entry & Authorised Release
        $resultWorkflow = app(ResultWorkflow::class);
        $draftResult = $resultWorkflow->enterResult($order, [
            'interpretation' => 'HBV DNA detected. High viral replication observed. Clinical correlation advised.',
            'is_critical' => false,
            'items' => [
                [
                    'analyte' => 'HBV DNA Quantitative PCR',
                    'loinc_code' => '42595-9',
                    'value' => '1,250,000',
                    'unit' => 'IU/mL',
                    'reference_range' => 'Not detected (< 10 IU/mL)',
                    'flag' => 'high',
                ],
            ],
        ], $scientistActor);

        $this->assertEquals(ResultStatus::Draft, $draftResult->status);
        $this->assertEquals(1, $draftResult->version);

        // Submit for review
        $resultWorkflow->submitForReview($draftResult, $scientistActor);
        $this->assertEquals(ResultStatus::PendingReview, $draftResult->fresh()->status);
        $this->assertEquals(OrderStatus::AwaitingResultReview, $order->fresh()->status);

        // Pathologist releases result (enforcing separation of duties from scientist)
        $releasedResult = $resultWorkflow->releaseResult(
            $draftResult,
            $pathologistActor,
            'Methodology validated. Controls within acceptable limits.'
        );

        $this->assertEquals(ResultStatus::Released, $releasedResult->fresh()->status);
        $this->assertEquals(OrderStatus::Completed, $order->fresh()->status);

        // 9. Finance: Invoicing & Payment Allocation
        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->createForOrder($order, $opsActor);

        $this->assertEquals('issued', $invoice->status);
        $this->assertEquals($order->financials->institution_total, $invoice->total);
        $this->assertEquals($order->financials->institution_total, $invoice->balance_due);

        // Hospital pays invoice via bank transfer
        $payment = $invoiceService->recordPayment([
            'organization_id' => $hospital->id,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'method' => 'bank_transfer',
            'provider_reference' => 'CBG-TX-2026-88123',
            'invoice_id' => $invoice->id,
        ], $opsActor);

        $this->assertEquals('confirmed', $payment->status);
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertEquals('0.00', $invoice->fresh()->balance_due);

        // 10. Financial Reconciliation Run
        $reconcile = app(ReconciliationEngine::class);
        $run = $reconcile->run(now()->subDay(), now()->addDay(), 'GHS', $opsActor);

        $this->assertEquals(1, $run->orders_checked);
        $this->assertEquals(0, $run->discrepancy_count);
        $this->assertEquals('450.00', $run->totals['total_gmv']);
        $this->assertEquals('54.00', $run->totals['total_platform_fees']); // 12% of 450
        $this->assertEquals('396.00', $run->totals['total_lab_payables']); // 450 - 54
    }
}
