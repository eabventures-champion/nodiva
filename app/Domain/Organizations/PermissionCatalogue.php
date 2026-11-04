<?php

declare(strict_types=1);

namespace App\Domain\Organizations;

/**
 * Canonical permission and role catalogue. Seeded into the database; roles are organisation-type specific.
 *
 * The clinical boundary is explicit: `results.view_clinical` and `patients.view_identifiable` are never granted to
 * platform roles. Platform staff may only access clinical data through the audited `ops.clinical.break_glass` flow.
 */
final class PermissionCatalogue
{
    /** @return array<string, array{0:string,1:string,2:bool}> key => [name, group, sensitive] */
    public static function permissions(): array
    {
        return [
            // Organisation administration (all types)
            'org.manage' => ['Manage organisation profile & branches', 'organisation', false],
            'users.manage' => ['Manage users and roles', 'organisation', true],
            'finance.view' => ['View invoices and statements', 'finance', false],
            'finance.pay' => ['Record payment notifications', 'finance', false],
            'support.manage' => ['Raise and manage support tickets & disputes', 'support', false],
            'integrations.manage' => ['Manage API credentials', 'organisation', true],

            // Hospital
            'patients.manage' => ['Register patients and consent', 'clinical', true],
            'patients.view_identifiable' => ['View patient-identifiable information', 'clinical', true],
            'orders.create' => ['Create and submit referral orders', 'orders', false],
            'orders.view' => ['View referral orders', 'orders', false],
            'orders.cancel' => ['Cancel referral orders', 'orders', false],
            'specimens.dispatch' => ['Record collection and dispatch events', 'specimens', false],
            'results.view_clinical' => ['View clinical results and documents', 'clinical', true],
            'results.acknowledge_critical' => ['Acknowledge critical results', 'clinical', true],

            // Laboratory
            'lab.profile.manage' => ['Manage laboratory profile, capacity & regions', 'laboratory', false],
            'lab.qualifications.manage' => ['Submit qualification documents', 'laboratory', false],
            'catalogue.manage' => ['Manage test catalogue & pricing', 'laboratory', false],
            'orders.respond' => ['Accept, reject or query incoming orders', 'orders', false],
            'specimens.receive' => ['Record specimen receipt, acceptance & rejection', 'specimens', false],
            'results.enter' => ['Enter results and upload documents', 'clinical', true],
            'results.release' => ['Authorise and release results', 'clinical', true],

            // Corporate
            'programs.manage' => ['Manage screening programmes & sessions', 'corporate', false],
            'employees.manage' => ['Import and manage employee records', 'corporate', true],
            'program_reports.view' => ['View aggregate programme reports', 'corporate', false],
            'program_results.view_individual' => ['View individual outcomes (consented only)', 'clinical', true],

            // NODIVA operations
            'ops.organizations.manage' => ['Approve, suspend and reactivate partners', 'operations', true],
            'ops.qualifications.verify' => ['Verify laboratory qualifications', 'operations', true],
            'ops.orders.monitor' => ['Monitor network orders (pseudonymised)', 'operations', false],
            'ops.orders.reassign' => ['Reassign and escalate orders', 'operations', true],
            'ops.orders.force_close' => ['Close orders by documented exception', 'operations', true],
            'ops.logistics.manage' => ['Coordinate collection and dispatch', 'operations', false],
            'ops.catalogue.manage' => ['Manage master test catalogue', 'operations', false],
            'ops.finance.manage' => ['Manage fees, invoices, payments & settlements', 'operations', true],
            'ops.reconcile' => ['Run financial reconciliation', 'operations', true],
            'ops.audit.view' => ['View audit logs', 'operations', true],
            'ops.settings.manage' => ['Manage platform settings & matching rules', 'operations', true],
            'ops.support.manage' => ['Manage tickets, disputes & incidents', 'operations', false],
            'ops.clinical.break_glass' => ['Emergency audited access to clinical records', 'operations', true],
        ];
    }

    /** @return array<string, array{name:string,type:string,admin:bool,description:string,permissions:list<string>}> */
    public static function roles(): array
    {
        $orgAdmin = ['org.manage', 'users.manage', 'finance.view', 'support.manage', 'integrations.manage'];

        return [
            'hospital_admin' => ['name' => 'Hospital administrator', 'type' => 'hospital', 'admin' => true, 'description' => 'Full administrative control of the hospital account, including clinical access.',
                'permissions' => [...$orgAdmin, 'finance.pay', 'patients.manage', 'patients.view_identifiable', 'orders.create', 'orders.view', 'orders.cancel', 'specimens.dispatch', 'results.view_clinical', 'results.acknowledge_critical']],
            'hospital_clinician' => ['name' => 'Clinician', 'type' => 'hospital', 'admin' => false, 'description' => 'Creates referrals and reviews results.',
                'permissions' => ['patients.manage', 'patients.view_identifiable', 'orders.create', 'orders.view', 'orders.cancel', 'specimens.dispatch', 'results.view_clinical', 'results.acknowledge_critical', 'support.manage']],
            'hospital_coordinator' => ['name' => 'Referral coordinator', 'type' => 'hospital', 'admin' => false, 'description' => 'Tracks orders and specimens; no clinical result access.',
                'permissions' => ['orders.view', 'specimens.dispatch', 'support.manage']],
            'hospital_finance' => ['name' => 'Finance officer', 'type' => 'hospital', 'admin' => false, 'description' => 'Invoices and payments only.',
                'permissions' => ['finance.view', 'finance.pay', 'orders.view', 'support.manage']],

            'lab_admin' => ['name' => 'Laboratory administrator', 'type' => 'laboratory', 'admin' => true, 'description' => 'Profile, catalogue, qualifications, team and order intake.',
                'permissions' => [...$orgAdmin, 'lab.profile.manage', 'lab.qualifications.manage', 'catalogue.manage', 'orders.view', 'orders.respond', 'specimens.receive']],
            'lab_scientist' => ['name' => 'Laboratory scientist', 'type' => 'laboratory', 'admin' => false, 'description' => 'Receives specimens and enters results.',
                'permissions' => ['orders.view', 'orders.respond', 'specimens.receive', 'results.enter']],
            'lab_pathologist' => ['name' => 'Authorising pathologist', 'type' => 'laboratory', 'admin' => false, 'description' => 'Reviews, authorises and releases results.',
                'permissions' => ['orders.view', 'results.enter', 'results.release']],
            'lab_finance' => ['name' => 'Laboratory finance', 'type' => 'laboratory', 'admin' => false, 'description' => 'Settlements and statements.',
                'permissions' => ['finance.view', 'orders.view', 'support.manage']],

            'corporate_admin' => ['name' => 'Corporate administrator', 'type' => 'corporate', 'admin' => true, 'description' => 'Programmes, employees, invoices. Aggregate reporting only.',
                'permissions' => [...$orgAdmin, 'finance.pay', 'programs.manage', 'employees.manage', 'program_reports.view']],
            'corporate_hr' => ['name' => 'HR coordinator', 'type' => 'corporate', 'admin' => false, 'description' => 'Schedules employees; cannot see individual outcomes.',
                'permissions' => ['programs.manage', 'employees.manage', 'program_reports.view']],
            'corporate_physician' => ['name' => 'Occupational physician', 'type' => 'corporate', 'admin' => false, 'description' => 'May view individual outcomes where the employee consented.',
                'permissions' => ['program_reports.view', 'program_results.view_individual']],

            'platform_admin' => ['name' => 'Platform administrator', 'type' => 'platform', 'admin' => true, 'description' => 'Full network administration. No routine clinical access.',
                'permissions' => ['users.manage', 'ops.organizations.manage', 'ops.qualifications.verify', 'ops.orders.monitor', 'ops.orders.reassign', 'ops.orders.force_close', 'ops.logistics.manage', 'ops.catalogue.manage', 'ops.finance.manage', 'ops.reconcile', 'ops.audit.view', 'ops.settings.manage', 'ops.support.manage']],
            'platform_operations' => ['name' => 'Network operations', 'type' => 'platform', 'admin' => false, 'description' => 'Order routing, logistics and exceptions (pseudonymised).',
                'permissions' => ['ops.orders.monitor', 'ops.orders.reassign', 'ops.logistics.manage', 'ops.support.manage', 'ops.qualifications.verify']],
            'platform_finance' => ['name' => 'Platform finance', 'type' => 'platform', 'admin' => false, 'description' => 'Fees, invoices, payments, settlements and reconciliation.',
                'permissions' => ['ops.orders.monitor', 'ops.finance.manage', 'ops.reconcile']],
            'platform_compliance' => ['name' => 'Compliance officer', 'type' => 'platform', 'admin' => false, 'description' => 'Audit, qualifications and break-glass clinical access.',
                'permissions' => ['ops.audit.view', 'ops.qualifications.verify', 'ops.orders.monitor', 'ops.clinical.break_glass', 'ops.support.manage']],
        ];
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::permissions());
    }
}
