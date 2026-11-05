<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use App\Models\Organization;
use App\Models\User;
use App\Services\DashboardNotificationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_platform_admin_can_send_direct_communication_to_hospital(): void
    {
        $admin = User::where('email', 'admin@nodiva.com')->firstOrFail();
        $hospital = Organization::where('type', 'hospital')->firstOrFail();

        // 1. Visit Compose Page
        $response = $this->actingAs($admin)->get(route('ops.communications.create', ['partner_id' => $hospital->id]));
        $response->assertOk();
        $response->assertSee('Compose Institutional Communication');

        // 2. Post new direct communication
        $postResponse = $this->actingAs($admin)->post(route('ops.communications.store'), [
            'recipient_type' => 'direct',
            'recipient_organization_id' => $hospital->id,
            'category' => 'operational',
            'priority' => 'urgent',
            'subject' => 'Updated Specimen Pickup Courier Window',
            'message' => 'Please note our courier dispatch window has shifted to 14:00 GMT daily.',
        ]);

        $postResponse->assertRedirect();

        $thread = CommunicationThread::where('subject', 'Updated Specimen Pickup Courier Window')->first();
        $this->assertNotNull($thread);
        $this->assertEquals('urgent', $thread->priority);
        $this->assertEquals($hospital->id, $thread->recipient_organization_id);
        $this->assertEquals(1, $thread->messages()->count());

        // 3. Hospital Admin receives notification
        $hospitalDoctor = User::where('email', 'doctor@ridgespecialist.com')->firstOrFail();
        $service = app(DashboardNotificationService::class);
        $notifications = $service->getNotifications($hospital, $hospitalDoctor);

        $foundCommNotification = false;
        foreach ($notifications as $n) {
            if ($n['type'] === 'communication' && str_contains($n['title'], 'Updated Specimen Pickup Courier Window')) {
                $foundCommNotification = true;
                $this->assertEquals('CRITICAL NOTICE', $n['badge']);
                break;
            }
        }
        $this->assertTrue($foundCommNotification, 'Communication notification was not delivered to the Hospital dashboard feed.');

        // 4. Hospital Admin views communication and posts reply
        $viewResponse = $this->actingAs($hospitalDoctor)->get(route('hospital.communications.show', $thread->uuid));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Updated Specimen Pickup Courier Window');
        $viewResponse->assertSee('Please note our courier dispatch window has shifted');

        $replyResponse = $this->actingAs($hospitalDoctor)->post(route('hospital.communications.reply', $thread->uuid), [
            'message' => 'Acknowledged. Our laboratory coordinator has notified our phlebotomy team.',
        ]);
        $replyResponse->assertRedirect();
        $this->assertEquals(2, $thread->fresh()->messages()->count());

        // 5. Platform Admin sees reply notification and thread status
        $opsOrg = Organization::where('name', 'NODIVA Operations Network')->firstOrFail();
        $opsNotifications = $service->getNotifications($opsOrg, $admin);

        $foundReply = false;
        foreach ($opsNotifications as $n) {
            if ($n['type'] === 'communication' && str_contains($n['title'], 'Updated Specimen Pickup Courier Window')) {
                $foundReply = true;
                break;
            }
        }
        $this->assertTrue($foundReply, 'Platform Admin did not receive reply notification.');

        // 6. Platform Admin marks thread as closed
        $closeResponse = $this->actingAs($admin)->post(route('ops.communications.toggle-status', $thread->uuid));
        $closeResponse->assertRedirect();
        $this->assertEquals('closed', $thread->fresh()->status);
    }

    public function test_platform_admin_can_broadcast_to_all_laboratories(): void
    {
        $admin = User::where('email', 'admin@nodiva.com')->firstOrFail();
        $lab = Organization::where('type', 'laboratory')->firstOrFail();
        $labScientist = User::where('email', 'scientist@synlab-gh.example')->firstOrFail();

        // Send Broadcast
        $response = $this->actingAs($admin)->post(route('ops.communications.store'), [
            'recipient_type' => 'all_laboratories',
            'category' => 'compliance',
            'priority' => 'normal',
            'subject' => 'Annual ISO 15189 Accreditation Audit Schedule',
            'message' => 'All network laboratories must ensure their annual HEFRA / ISO accreditation documents are up to date.',
        ]);

        $response->assertRedirect();

        $thread = CommunicationThread::where('subject', 'Annual ISO 15189 Accreditation Audit Schedule')->firstOrFail();
        $this->assertTrue($thread->isBroadcast());

        // Verify Lab Portal index displays this broadcast
        $labIndexResponse = $this->actingAs($labScientist)->get(route('lab.communications.index'));
        $labIndexResponse->assertOk();
        $labIndexResponse->assertSee('Annual ISO 15189 Accreditation Audit Schedule');
        $labIndexResponse->assertSee('Platform Broadcast');

        // Verify Lab cannot close a platform broadcast
        $forbiddenClose = $this->actingAs($labScientist)->post(route('lab.communications.toggle-status', $thread->uuid));
        $forbiddenClose->assertForbidden();
    }

    public function test_hospital_and_laboratory_can_resolve_and_reopen_direct_threads(): void
    {
        $hospitalDoctor = User::where('email', 'doctor@ridgespecialist.com')->firstOrFail();
        $lab = Organization::where('type', 'laboratory')->firstOrFail();
        $labScientist = User::where('email', 'scientist@synlab-gh.example')->firstOrFail();

        // 1. Hospital creates direct communication to Laboratory
        $response = $this->actingAs($hospitalDoctor)->post(route('hospital.communications.store'), [
            'recipient_organization_id' => $lab->id,
            'category' => 'operational',
            'priority' => 'high',
            'subject' => 'Specimen Ingestion Inquiry #8821',
            'message' => 'Please confirm accessioning of frozen plasma specimen sent this morning.',
        ]);
        $response->assertRedirect();

        $thread = CommunicationThread::where('subject', 'Specimen Ingestion Inquiry #8821')->firstOrFail();
        $this->assertEquals('open', $thread->status);

        // 2. Lab views and replies
        $replyResponse = $this->actingAs($labScientist)->post(route('lab.communications.reply', $thread->uuid), [
            'message' => 'Sample received and accessioned in cryogenic batch.',
        ]);
        $replyResponse->assertRedirect();

        // 3. Lab marks as resolved / closed
        $closeResponse = $this->actingAs($labScientist)->post(route('lab.communications.toggle-status', $thread->uuid));
        $closeResponse->assertRedirect();
        $this->assertEquals('closed', $thread->fresh()->status);
        $this->assertEquals($labScientist->id, $thread->fresh()->closed_by);

        // 4. Hospital visits and sees resolved banner & can reopen by replying
        $viewResponse = $this->actingAs($hospitalDoctor)->get(route('hospital.communications.show', $thread->uuid));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Thread Marked as Resolved / Closed');

        // 5. Hospital sends a reply which automatically reopens the thread
        $reopenReply = $this->actingAs($hospitalDoctor)->post(route('hospital.communications.reply', $thread->uuid), [
            'message' => 'Quick follow up: what is the estimated turnaround time?',
        ]);
        $reopenReply->assertRedirect();
        $this->assertEquals('open', $thread->fresh()->status);
        $this->assertNull($thread->fresh()->closed_by);
        $this->assertNull($thread->fresh()->closed_at);
    }
}
