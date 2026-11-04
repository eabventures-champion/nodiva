<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SystemMaintenanceController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * Clear all transactional data across the NODIVA platform, strictly preserving:
     * 1. Master Diagnostic Test Catalogue (specialties, specimen tubes, formulations)
     * 2. Operations Network & CTO login credentials and access governance
     * 3. Laboratory Diagnostic Menu & Capacity (all partner test catalogue entries, pricing, TAT)
     */
    public function purgeDatabase(Request $request)
    {
        $org = $this->current->organization();
        $user = $request->user();

        // Security check: Only CTO in the platform organization can purge the database
        if (! $org->isPlatform() || ! $user?->isCto()) {
            abort(403, 'Unauthorized maintenance access. Database purge is restricted exclusively to the Chief Technical Officer (CTO).');
        }

        $validated = $request->validate([
            'confirmation_code' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (strtoupper(trim($validated['confirmation_code'])) !== 'PURGE') {
            return redirect()->back()->with('error', 'Database purge aborted: Confirmation code must be exactly "PURGE".');
        }

        if (! Hash::check($validated['password'], $user->password)) {
            return redirect()->back()->with('error', 'Database purge aborted: Invalid CTO authentication password.');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 1. Transactional Referrals, Items, Status, and Custody Events
            DB::table('referral_order_items')->truncate();
            DB::table('order_status_events')->truncate();
            DB::table('order_assignments')->truncate();
            DB::table('chain_of_custody_events')->truncate();
            DB::table('specimens')->truncate();
            DB::table('dispatches')->truncate();

            // 2. Diagnostic Results, Documents, and Acknowledgements
            DB::table('diagnostic_result_items')->truncate();
            DB::table('diagnostic_results')->truncate();
            if (DB::getSchemaBuilder()->hasTable('result_documents')) {
                DB::table('result_documents')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('critical_result_acknowledgements')) {
                DB::table('critical_result_acknowledgements')->truncate();
            }

            // 3. Referral Orders Master
            DB::table('referral_orders')->truncate();

            // 4. Financial Records, Invoices, Payments, Settlements
            DB::table('invoice_items')->truncate();
            DB::table('invoices')->truncate();
            DB::table('order_financials')->truncate();
            if (DB::getSchemaBuilder()->hasTable('payments')) {
                DB::table('payments')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('payment_allocations')) {
                DB::table('payment_allocations')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('credit_notes')) {
                DB::table('credit_notes')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('financial_adjustments')) {
                DB::table('financial_adjustments')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('laboratory_settlement_items')) {
                DB::table('laboratory_settlement_items')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('laboratory_settlements')) {
                DB::table('laboratory_settlements')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('reconciliation_items')) {
                DB::table('reconciliation_items')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('reconciliation_runs')) {
                DB::table('reconciliation_runs')->truncate();
            }

            // 5. Patients and Consents (Hospital Transactions)
            DB::table('patient_consents')->truncate();
            DB::table('patients')->truncate();

            // 6. Laboratory Diagnostic Menu & Capacity (Cleared so each lab configures their distinct codes, prices & TAT)
            DB::table('test_catalogue_entries')->truncate();

            // 7. Logs, Notifications, and Tickets
            DB::table('audit_logs')->truncate();
            if (DB::getSchemaBuilder()->hasTable('notifications')) {
                DB::table('notifications')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('support_tickets')) {
                DB::table('support_tickets')->truncate();
            }

            // 8. Institutional Partners & Partner Personnel
            if (DB::getSchemaBuilder()->hasTable('organization_invitations')) {
                DB::table('organization_invitations')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('laboratory_profiles')) {
                DB::table('laboratory_profiles')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('laboratory_qualifications')) {
                DB::table('laboratory_qualifications')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('screening_programs')) {
                DB::table('screening_programs')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('employee_records')) {
                DB::table('employee_records')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('departments')) {
                DB::table('departments')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('organization_branches')) {
                DB::table('organization_branches')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('contracts')) {
                DB::table('contracts')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('laboratory_service_region')) {
                DB::table('laboratory_service_region')->truncate();
            }
            if (DB::getSchemaBuilder()->hasTable('laboratory_pricing_rules')) {
                DB::table('laboratory_pricing_rules')->truncate();
            }

            // Clear all non-platform organizations
            DB::table('organizations')->where('type', '!=', 'platform')->delete();

            // Identify and standardize Platform Organization & CTO User to ID = 1
            $platformOrg = DB::table('organizations')->where('type', 'platform')->first();
            $ctoUser = DB::table('users')->where('email', 'admin@nodiva.com')->first()
                ?? DB::table('users')->where('id', $user->id)->first();

            if ($ctoUser) {
                DB::table('users')->where('id', '!=', $ctoUser->id)->delete();
            }

            if ($platformOrg && $platformOrg->id !== 1) {
                $oldOrgId = $platformOrg->id;
                DB::table('organizations')->where('id', $oldOrgId)->update(['id' => 1]);
                $this->updateForeignKeyAcrossDatabase('organization_id', $oldOrgId, 1);
                $this->updateForeignKeyAcrossDatabase('actor_organization_id', $oldOrgId, 1);
            }

            if ($ctoUser && $ctoUser->id !== 1) {
                $oldUserId = $ctoUser->id;
                DB::table('users')->where('id', $oldUserId)->update([
                    'id' => 1,
                    'current_membership_id' => 1,
                ]);
                $this->updateForeignKeyAcrossDatabase('user_id', $oldUserId, 1);
                $this->updateForeignKeyAcrossDatabase('actor_id', $oldUserId, 1);
                $this->updateForeignKeyAcrossDatabase('approved_by', $oldUserId, 1);
                $this->updateForeignKeyAcrossDatabase('created_by', $oldUserId, 1);
                $this->updateForeignKeyAcrossDatabase('updated_by', $oldUserId, 1);
            }

            // Re-establish CTO Platform Membership
            $platformAdminRoleId = DB::table('roles')->where('key', 'platform_admin')->value('id') ?? 12;
            DB::table('organization_memberships')->truncate();
            DB::table('organization_memberships')->insert([
                'id' => 1,
                'user_id' => 1,
                'organization_id' => 1,
                'role_id' => $platformAdminRoleId,
                'department_id' => null,
                'status' => 'active',
                'permissions' => null,
                'invited_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Set AUTO_INCREMENT to 2 so future entries sequence from 2
            DB::statement("ALTER TABLE users AUTO_INCREMENT = 2;");
            DB::statement("ALTER TABLE organizations AUTO_INCREMENT = 2;");
            DB::statement("ALTER TABLE organization_memberships AUTO_INCREMENT = 2;");

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Refresh org model after ID normalization
            $freshOrg = \App\Models\Organization::find(1) ?? $org;

            // Log fresh audit event for the purge
            $this->audit->log(
                'platform.database_purged',
                $freshOrg,
                [
                    'purged_by' => $user->name . ' (' . $user->email . ')',
                    'timestamp' => now()->toIso8601String(),
                    'retained' => [
                        'master_diagnostic_catalogue' => true,
                        'platform_ops_org_and_cto' => true,
                    ],
                    'cleared' => [
                        'transactional_referrals' => true,
                        'clinical_results' => true,
                        'specimens' => true,
                        'invoices_and_finance' => true,
                        'partner_organizations' => true,
                        'partner_users' => true,
                        'laboratory_menus_and_capacity' => true,
                    ],
                ],
                'success'
            );

            return redirect()->back()->with('success', 'Platform database successfully sanitized! All transactional orders, results, custody events, invoices, partner organizations, and partner users have been cleared. Platform Operations and CTO credentials are preserved at ID #1 (auto-increment reset to #2).');
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return redirect()->back()->with('error', 'Database purge failed: ' . $e->getMessage());
        }
    }

    private function updateForeignKeyAcrossDatabase(string $columnName, int $oldVal, int $newVal): void
    {
        $dbName = config('database.connections.mysql.database');
        $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", [$dbName]);

        foreach ($tables as $t) {
            $tableName = $t->TABLE_NAME;
            if (DB::getSchemaBuilder()->hasColumn($tableName, $columnName)) {
                DB::table($tableName)->where($columnName, $oldVal)->update([$columnName => $newVal]);
            }
        }
    }
}
