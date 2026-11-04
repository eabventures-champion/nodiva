<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Organizations\Enums\OrganizationStatus;
use App\Domain\Organizations\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\User;
use App\Services\DashboardNotificationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_notification_service_generates_notifications_for_operations(): void
    {
        $service = app(DashboardNotificationService::class);
        $admin = User::where('email', 'admin@nodiva.com')->firstOrFail();
        $org = Organization::where('name', 'NODIVA Operations Network')->firstOrFail();

        // Create a pending hospital access request
        $hospital = new Organization([
            'name' => 'Pending General Hospital',
            'email' => 'pending@hospital.example',
            'phone' => '+233302000111',
            'city' => 'Kumasi',
        ]);
        $hospital->type = OrganizationType::Hospital;
        $hospital->status = OrganizationStatus::Pending;
        $hospital->save();

        $notifications = $service->getNotifications($org, $admin);

        $this->assertNotEmpty($notifications);
        $foundAccessRequest = false;
        foreach ($notifications as $n) {
            if ($n['type'] === 'access_request' && str_contains($n['title'], 'Pending General Hospital')) {
                $foundAccessRequest = true;
                break;
            }
        }
        $this->assertTrue($foundAccessRequest, 'Pending access request notification was not found for Operations.');
    }

    public function test_notification_bell_is_rendered_in_operations_dashboard(): void
    {
        $admin = User::where('email', 'admin@nodiva.com')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('ops.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Institutional Notifications');
    }

    public function test_notification_bell_is_rendered_in_hospital_dashboard(): void
    {
        $doctor = User::where('email', 'doctor@ridgespecialist.com')->firstOrFail();

        $response = $this->actingAs($doctor)->get(route('hospital.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Institutional Notifications');
    }

    public function test_notification_bell_is_rendered_in_laboratory_dashboard(): void
    {
        $scientist = User::where('email', 'scientist@synlab-gh.example')->firstOrFail();

        $response = $this->actingAs($scientist)->get(route('lab.reception.index'));
        $response->assertStatus(200);
        $response->assertSee('Institutional Notifications');
    }

    public function test_notification_bell_is_rendered_in_corporate_dashboard(): void
    {
        $manager = User::where('email', 'hr@tullow.example')->firstOrFail();

        $response = $this->actingAs($manager)->get(route('corporate.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Institutional Notifications');
    }
}
