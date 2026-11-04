<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Organizations\Actor;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Domain\Specimens\Enums\SpecimenStatus;
use App\Domain\Specimens\SpecimenCustody;
use App\Domain\Support\ReferenceGenerator;
use App\Models\DiagnosticTest;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\ReferralOrder;
use App\Models\ReferralOrderItem;
use App\Models\Specimen;
use App\Models\SpecimenType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private Organization $hospital;
    private Organization $otherHospital;
    private User $otherDoctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->hospital = Organization::where('name', 'Ridge Specialist Hospital')->firstOrFail();
        $this->doctor = User::where('email', 'doctor@ridgespecialist.com')->firstOrFail();

        // Second hospital for multi-tenant boundary test
        $this->otherHospital = new Organization([
            'name' => 'Korle Bu Teaching Hospital',
            'email' => 'lab@korlebu.example',
            'phone' => '+233302111222',
            'currency' => 'GHS',
            'country_code' => 'GH',
        ]);
        $this->otherHospital->type = \App\Domain\Organizations\Enums\OrganizationType::Hospital;
        $this->otherHospital->status = \App\Domain\Organizations\Enums\OrganizationStatus::Active;
        $this->otherHospital->save();

        $this->otherDoctor = User::create([
            'name' => 'Dr. Ama Serwaa',
            'email' => 'ama@korlebu.example',
            'password' => bcrypt('Password123!'),
        ]);

        $role = \App\Models\Role::where('key', 'hospital_clinician')->firstOrFail();
        $m = \App\Models\OrganizationMembership::create([
            'user_id' => $this->otherDoctor->id,
            'organization_id' => $this->otherHospital->id,
            'role_id' => $role->id,
        ]);
        $this->otherDoctor->current_membership_id = $m->id;
        $this->otherDoctor->save();
    }

    public function test_can_view_hospital_create_referral_form(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('hospital.orders.create'));
        $response->assertStatus(200);
        $response->assertSee('Create New Diagnostic Referral');
        $response->assertSee('Informed Referral Consent Granted');
    }

    public function test_can_submit_new_referral_order_successfully(): void
    {
        $test = DiagnosticTest::where('code', 'HBV_VIRAL_LOAD')->firstOrFail();

        $payload = [
            'patient_mode' => 'new',
            'first_name' => 'Esi',
            'last_name' => 'Addae',
            'medical_record_number' => 'MRN-TEST-4421',
            'date_of_birth' => '1992-07-15',
            'sex' => 'female',
            'phone' => '+233240998877',
            'consent_confirmed' => '1',
            'clinical_indication' => 'Chronic viral hepatitis staging',
            'priority' => 'routine',
            'collection_method' => 'courier',
            'test_ids' => [$test->id],
        ];

        $response = $this->actingAs($this->doctor)->post(route('hospital.orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $order = ReferralOrder::where('requesting_organization_id', $this->hospital->id)->latest('id')->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('hospital.orders.show', $order));

        $this->assertEquals($this->hospital->id, $order->requesting_organization_id);
        $this->assertNotEmpty($order->items);
        $this->assertNotEmpty($order->specimens);
    }

    public function test_can_view_diagnostic_test_catalogue(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('hospital.catalogue.index'));
        $response->assertStatus(200);
        $response->assertSee('Diagnostic Test Catalogue');
        $response->assertSee('HBV_VIRAL_LOAD');
    }

    public function test_can_view_specimen_custody_page(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('hospital.specimens.index'));
        $response->assertStatus(200);
        $response->assertSee('Specimen Custody & Courier Handover');
    }

    public function test_can_view_invoices_page(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('hospital.invoices.index'));
        $response->assertStatus(200);
        $response->assertSee('Invoices & Statements');
    }

    public function test_cannot_view_referral_order_from_another_hospital(): void
    {
        // Create an order for the second hospital
        $test = DiagnosticTest::where('code', 'HBV_VIRAL_LOAD')->firstOrFail();
        $patient = Patient::create([
            'organization_id' => $this->otherHospital->id,
            'mrn_hash' => Patient::mrnHash($this->otherHospital->uuid, 'MRN-KB-1'),
            'medical_record_number' => 'MRN-KB-1',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'reference' => 'PT-KB-001',
            'created_by' => $this->otherDoctor->id,
        ]);

        $foreignOrder = new ReferralOrder();
        $foreignOrder->reference = ReferenceGenerator::make('RF', 'referral_orders', 'reference');
        $foreignOrder->requesting_organization_id = $this->otherHospital->id;
        $foreignOrder->patient_id = $patient->id;
        $foreignOrder->created_by = $this->otherDoctor->id;
        $foreignOrder->status = OrderStatus::Draft;
        $foreignOrder->priority = 'routine';
        $foreignOrder->collection_method = 'courier';
        $foreignOrder->clinical_indication = 'Test';
        $foreignOrder->currency = 'GHS';
        $foreignOrder->save();

        // Attempt to access foreign order as Ridge hospital doctor
        $response = $this->actingAs($this->doctor)->get(route('hospital.orders.show', $foreignOrder));
        $response->assertStatus(403);
    }

    public function test_can_view_own_referral_order_details(): void
    {
        $patient = Patient::create([
            'organization_id' => $this->hospital->id,
            'mrn_hash' => Patient::mrnHash($this->hospital->uuid, 'MRN-OWN-1'),
            'medical_record_number' => 'MRN-OWN-1',
            'first_name' => 'Jane',
            'last_name' => 'Mensah',
            'date_of_birth' => '1995-05-12',
            'sex' => 'female',
            'reference' => 'PT-OWN-001',
            'created_by' => $this->doctor->id,
        ]);

        $order = new ReferralOrder();
        $order->reference = ReferenceGenerator::make('RF', 'referral_orders', 'reference');
        $order->requesting_organization_id = $this->hospital->id;
        $order->patient_id = $patient->id;
        $order->created_by = $this->doctor->id;
        $order->status = OrderStatus::Completed;
        $order->priority = 'routine';
        $order->collection_method = 'courier';
        $order->clinical_indication = 'Routine follow-up';
        $order->currency = 'GHS';
        $order->save();

        \App\Models\Dispatch::create([
            'referral_order_id' => $order->id,
            'type' => 'specimen_pickup',
            'carrier_name' => 'DHL Health Logistics',
            'tracking_reference' => 'DHL-TEST-1234',
            'pickup_location' => 'Ridge Hospital Lab reception',
            'status' => 'delivered',
            'created_by' => $this->doctor->id,
        ]);

        $response = $this->actingAs($this->doctor)->get(route('hospital.orders.show', $order));
        $response->assertStatus(200);
        $response->assertSee($order->reference);
        $response->assertSee('Jane Mensah');
    }

    public function test_hospital_can_collect_and_dispatch_specimen_when_awaiting_lab_acceptance(): void
    {
        $test = DiagnosticTest::where('code', 'HBV_VIRAL_LOAD')->firstOrFail();

        // 1. Submit referral order
        $payload = [
            'patient_mode' => 'new',
            'first_name' => 'Akosua',
            'last_name' => 'Osei',
            'medical_record_number' => 'MRN-DSP-9988',
            'date_of_birth' => '1990-01-01',
            'sex' => 'female',
            'phone' => '+233201122334',
            'consent_confirmed' => '1',
            'clinical_indication' => 'Pre-treatment HBV viral quantitation',
            'priority' => 'routine',
            'collection_method' => 'courier',
            'test_ids' => [$test->id],
        ];

        $this->actingAs($this->doctor)->post(route('hospital.orders.store'), $payload);

        $order = ReferralOrder::where('requesting_organization_id', $this->hospital->id)->latest('id')->firstOrFail();
        $this->assertSame(OrderStatus::AwaitingLabAcceptance, $order->status);

        $specimen = $order->specimens()->firstOrFail();
        $this->assertSame(\App\Domain\Specimens\Enums\SpecimenStatus::PendingCollection, $specimen->status);

        // 2. Phlebotomist marks specimen collected
        $collectResponse = $this->actingAs($this->doctor)->post(route('hospital.specimens.collect', $specimen), [
            'location' => 'Ridge Phlebotomy Room 2',
        ]);
        $collectResponse->assertSessionHasNoErrors();
        $collectResponse->assertRedirect();

        $specimen->refresh();
        $order->refresh();
        $this->assertSame(\App\Domain\Specimens\Enums\SpecimenStatus::Collected, $specimen->status);
        $this->assertSame(OrderStatus::SpecimenCollected, $order->status);

        // 3. Hand over to courier (dispatch consignment)
        $dispatchResponse = $this->actingAs($this->doctor)->post(route('hospital.specimens.dispatch'), [
            'order_id' => $order->id,
            'carrier_name' => 'Medical Express Courier (Ghana)',
            'tracking_reference' => 'MX-GH-998822',
            'pickup_location' => 'Ridge Specialist Hospital — Central Specimen Handover Bay',
        ]);
        $dispatchResponse->assertSessionHasNoErrors();
        $dispatchResponse->assertRedirect();

        $order->refresh();
        $specimen->refresh();
        $this->assertSame(OrderStatus::InTransit, $order->status);
        $this->assertSame(\App\Domain\Specimens\Enums\SpecimenStatus::InTransit, $specimen->status);
        $this->assertDatabaseHas('dispatches', [
            'referral_order_id' => $order->id,
            'tracking_reference' => 'MX-GH-998822',
            'carrier_name' => 'Medical Express Courier (Ghana)',
        ]);
    }

    public function test_specimen_index_displays_received_and_accepted_specimens(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('hospital.specimens.index'));
        $response->assertStatus(200);
        $response->assertSee('Received & Verified by Lab', false);
    }
}
