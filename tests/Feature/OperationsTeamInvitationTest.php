<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\TeamMemberInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OperationsTeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $coo;
    private Organization $platformOrg;
    private Role $operationsRole;
    private Role $financeRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->coo = User::where('email', 'admin@nodiva.com')->firstOrFail();
        $this->platformOrg = Organization::where('type', 'platform')->firstOrFail();
        $this->operationsRole = Role::where('key', 'platform_operations')->firstOrFail();
        $this->financeRole = Role::where('key', 'platform_finance')->firstOrFail();
    }

    public function test_coo_can_view_team_management_page(): void
    {
        $response = $this->actingAs($this->coo)->get(route('ops.team.index'));

        $response->assertStatus(200);
        $response->assertSee('Operations Team & Access Governance');
        $response->assertSee('Active Operations Personnel');
        $response->assertSee('Pending Access Invitations');
        $response->assertSee('Invite Team Member');
        $response->assertSee('Team & Access Governance');
        $response->assertSee($this->coo->name);
    }

    public function test_user_without_users_manage_permission_cannot_access_team_page(): void
    {
        // Create an operations staff member without users.manage permission
        $staffUser = User::create([
            'name' => 'Kofi Logistics',
            'email' => 'kofi.logistics@nodiva.example',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $membership = OrganizationMembership::create([
            'user_id' => $staffUser->id,
            'organization_id' => $this->platformOrg->id,
            'role_id' => $this->operationsRole->id,
            'permissions' => ['ops.orders.monitor', 'ops.logistics.manage'],
            'status' => 'active',
        ]);

        $staffUser->current_membership_id = $membership->id;
        $staffUser->save();

        $response = $this->actingAs($staffUser)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('ops.team.index'));

        $response->assertStatus(403);
    }

    public function test_coo_can_invite_team_member_with_granular_sidebar_permissions(): void
    {
        Mail::fake();

        $payload = [
            'email' => 'ama.boateng@nodiva.example',
            'name' => 'Ama Boateng',
            'job_title' => 'Logistics & Transit Specialist',
            'role_id' => $this->operationsRole->id,
            'permissions' => [
                'ops.orders.monitor',
                'ops.logistics.manage',
            ],
            'expires_in_days' => 7,
        ];

        $response = $this->actingAs($this->coo)->post(route('ops.team.invite'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('organization_invitations', [
            'organization_id' => $this->platformOrg->id,
            'email' => 'ama.boateng@nodiva.example',
            'name' => 'Ama Boateng',
            'job_title' => 'Logistics & Transit Specialist',
            'role_id' => $this->operationsRole->id,
            'status' => 'pending',
        ]);

        $invitation = OrganizationInvitation::where('email', 'ama.boateng@nodiva.example')->firstOrFail();
        $this->assertEquals(['ops.orders.monitor', 'ops.logistics.manage'], $invitation->permissions);

        Mail::assertSent(TeamMemberInvitationMail::class, function ($mail) use ($invitation) {
            return $mail->hasTo('ama.boateng@nodiva.example')
                && $mail->invitation->id === $invitation->id;
        });
    }

    public function test_invitee_can_view_acceptance_page_and_see_delegated_scopes(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->platformOrg->id,
            'email' => 'kwame.addo@nodiva.example',
            'name' => 'Kwame Addo',
            'job_title' => 'Finance Auditor',
            'role_id' => $this->financeRole->id,
            'permissions' => ['ops.finance.manage'],
            'token' => 'secure-invitation-token-12345',
            'invited_by' => $this->coo->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->get(route('invitations.accept', ['token' => $invitation->token]));

        $response->assertStatus(200);
        $response->assertSee('Accept Console Invitation');
        $response->assertSee('Kwame Addo');
        $response->assertSee('kwame.addo@nodiva.example');
        $response->assertSee('Financial Reconciliation');
        $response->assertSee('Activate Account & Launch Operations Console', false);
    }

    public function test_invitee_can_accept_invitation_set_password_and_is_logged_in_with_sidebar_filtering(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->platformOrg->id,
            'email' => 'selorm.mensah@nodiva.example',
            'name' => 'Selorm Mensah',
            'job_title' => 'Logistics Operator',
            'role_id' => $this->operationsRole->id,
            'permissions' => [
                'ops.orders.monitor',
                'ops.logistics.manage',
            ],
            'token' => 'token-selorm-accept-999',
            'invited_by' => $this->coo->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->post(route('invitations.process', ['token' => $invitation->token]), [
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
        ]);

        $response->assertRedirect(route('ops.dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'selorm.mensah@nodiva.example',
            'name' => 'Selorm Mensah',
            'status' => 'active',
        ]);

        $user = User::where('email', 'selorm.mensah@nodiva.example')->firstOrFail();
        $this->assertTrue(Hash::check('SecurePass2026!', $user->password));

        $membership = OrganizationMembership::where('user_id', $user->id)
            ->where('organization_id', $this->platformOrg->id)
            ->firstOrFail();

        $this->assertEquals(['ops.orders.monitor', 'ops.logistics.manage'], $membership->permissions);
        $this->assertEquals('active', $membership->status);

        $invitation->refresh();
        $this->assertEquals('accepted', $invitation->status);
        $this->assertNotNull($invitation->accepted_at);

        // Verify Sidebar Access enforcement on Dashboard
        $dashboardResponse = $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('ops.dashboard'));

        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Network Operations');
        $dashboardResponse->assertSee('Logistics & Couriers', false);

        // Menus NOT permitted must not appear in the sidebar
        $dashboardResponse->assertDontSee('Institutional Partners');
        $dashboardResponse->assertDontSee('Master Test Catalogue');
        $dashboardResponse->assertDontSee('Financial Reconciliation');
        $dashboardResponse->assertDontSee('Security & Audit Trail', false);
        $dashboardResponse->assertDontSee('Team & Access Governance', false);
    }

    public function test_invalid_or_expired_invitation_is_rejected(): void
    {
        // 1. Invalid token
        $response = $this->get(route('invitations.accept', ['token' => 'non-existent-token']));
        $response->assertStatus(200);
        $response->assertSee('Invitation Invalid or Expired');

        // 2. Expired token
        $expiredInvite = OrganizationInvitation::create([
            'organization_id' => $this->platformOrg->id,
            'email' => 'expired@nodiva.example',
            'name' => 'Expired User',
            'job_title' => 'Test',
            'role_id' => $this->operationsRole->id,
            'token' => 'expired-token-123',
            'invited_by' => $this->coo->id,
            'status' => 'pending',
            'expires_at' => now()->subDay(),
        ]);

        $responseExpired = $this->get(route('invitations.accept', ['token' => $expiredInvite->token]));
        $responseExpired->assertStatus(200);
        $responseExpired->assertSee('expired');
    }

    public function test_coo_can_update_member_permissions(): void
    {
        $staffUser = User::create([
            'name' => 'Esi Tamakloe',
            'email' => 'esi.tamakloe@nodiva.example',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $membership = OrganizationMembership::create([
            'user_id' => $staffUser->id,
            'organization_id' => $this->platformOrg->id,
            'role_id' => $this->operationsRole->id,
            'permissions' => ['ops.orders.monitor'],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->coo)->patch(route('ops.team.permissions', $membership), [
            'role_id' => $this->financeRole->id,
            'permissions' => ['ops.orders.monitor', 'ops.finance.manage', 'ops.audit.view'],
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $membership->refresh();
        $this->assertEquals($this->financeRole->id, $membership->role_id);
        $this->assertEquals(['ops.orders.monitor', 'ops.finance.manage', 'ops.audit.view'], $membership->permissions);
    }

    public function test_coo_can_revoke_and_resend_invitation(): void
    {
        Mail::fake();

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->platformOrg->id,
            'email' => 'yaw.revokeme@nodiva.example',
            'name' => 'Yaw Revokeme',
            'job_title' => 'Logistics Driver',
            'role_id' => $this->operationsRole->id,
            'token' => 'token-to-revoke-123',
            'invited_by' => $this->coo->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Resend
        $resendResponse = $this->actingAs($this->coo)->post(route('ops.team.invitations.resend', $invitation));
        $resendResponse->assertRedirect();
        $resendResponse->assertSessionHas('success');
        Mail::assertSent(TeamMemberInvitationMail::class);

        // Revoke
        $revokeResponse = $this->actingAs($this->coo)->delete(route('ops.team.invitations.revoke', $invitation));
        $revokeResponse->assertRedirect();
        $revokeResponse->assertSessionHas('success');

        $invitation->refresh();
        $this->assertEquals('revoked', $invitation->status);
    }

    public function test_coo_can_dynamically_create_new_platform_role(): void
    {
        $payload = [
            'name' => 'Logistics Dispatch Lead',
            'description' => 'Oversees courier routing and specimen cold-chain transit.',
            'is_admin' => false,
            'permissions' => [
                'ops.orders.monitor',
                'ops.logistics.manage',
            ],
        ];

        $response = $this->actingAs($this->coo)
            ->postJson(route('ops.team.roles.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'role' => [
                'name' => 'Logistics Dispatch Lead',
                'organization_type' => 'platform',
                'is_admin' => false,
            ],
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'Logistics Dispatch Lead',
            'organization_type' => 'platform',
            'is_admin' => false,
        ]);

        $role = Role::where('name', 'Logistics Dispatch Lead')->firstOrFail();
        $this->assertTrue($role->hasPermission('ops.orders.monitor'));
        $this->assertTrue($role->hasPermission('ops.logistics.manage'));
    }

    public function test_platform_administrator_cannot_edit_chief_technical_officer_role(): void
    {
        // Another platform administrator (e.g. Jane Addo)
        $adminRole = Role::where('key', 'platform_admin')->firstOrFail();
        $otherAdmin = User::create([
            'name' => 'Jane Addo',
            'email' => 'jane.addo@nodiva.example',
            'password' => Hash::make('Secret123!'),
            'job_title' => 'Financial Officer',
            'status' => 'active',
        ]);

        $otherMembership = OrganizationMembership::create([
            'user_id' => $otherAdmin->id,
            'organization_id' => $this->platformOrg->id,
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $otherAdmin->current_membership_id = $otherMembership->id;
        $otherAdmin->save();

        // CTO membership
        $ctoMembership = $this->coo->membershipFor($this->platformOrg);

        // Other administrator attempts to edit CTO
        $response = $this->actingAs($otherAdmin)
            ->withSession(['active_membership_id' => $otherMembership->id])
            ->patch(route('ops.team.permissions', $ctoMembership), [
                'role_id' => $this->financeRole->id,
                'permissions' => ['ops.finance.manage'],
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Privilege Denied: The Chief Technical Officer role is root-protected and cannot be edited by any other Platform Administrator.');

        // CTO role remains unchanged
        $ctoMembership->refresh();
        $this->assertEquals($adminRole->id, $ctoMembership->role_id);
    }

    public function test_only_chief_technical_officer_can_suspend_or_deactivate_accounts(): void
    {
        $adminRole = Role::where('key', 'platform_admin')->firstOrFail();
        $otherAdmin = User::create([
            'name' => 'Jane Addo',
            'email' => 'jane.addo2@nodiva.example',
            'password' => Hash::make('Secret123!'),
            'job_title' => 'Financial Officer',
            'status' => 'active',
        ]);

        $otherMembership = OrganizationMembership::create([
            'user_id' => $otherAdmin->id,
            'organization_id' => $this->platformOrg->id,
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        // Target staff user
        $targetStaff = User::create([
            'name' => 'Staff Member',
            'email' => 'staff.target@nodiva.example',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $targetMembership = OrganizationMembership::create([
            'user_id' => $targetStaff->id,
            'organization_id' => $this->platformOrg->id,
            'role_id' => $this->operationsRole->id,
            'status' => 'active',
        ]);

        // 1. Non-CTO platform admin tries to suspend target member -> Denied
        $deniedResponse = $this->actingAs($otherAdmin)
            ->withSession(['active_membership_id' => $otherMembership->id])
            ->patch(route('ops.team.permissions', $targetMembership), [
                'role_id' => $this->operationsRole->id,
                'status' => 'suspended',
            ]);

        $deniedResponse->assertRedirect();
        $deniedResponse->assertSessionHas('error', 'Privilege Denied: Only the Chief Technical Officer has the privilege to deactivate and suspend accounts.');
        $targetMembership->refresh();
        $this->assertEquals('active', $targetMembership->status);

        // 2. Chief Technical Officer suspends target member -> Allowed
        $allowedResponse = $this->actingAs($this->coo)
            ->patch(route('ops.team.permissions', $targetMembership), [
                'role_id' => $this->operationsRole->id,
                'status' => 'suspended',
            ]);

        $allowedResponse->assertRedirect();
        $allowedResponse->assertSessionHas('success');
        $targetMembership->refresh();
        $this->assertEquals('suspended', $targetMembership->status);
    }
}
