<?php

use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Corporate\DashboardController as CorporateDashboardController;
use App\Http\Controllers\Corporate\EmployeeController as CorporateEmployeeController;
use App\Http\Controllers\Corporate\InsightController as CorporateInsightController;
use App\Http\Controllers\Corporate\InvoiceController as CorporateInvoiceController;
use App\Http\Controllers\Corporate\SessionController as CorporateSessionController;
use App\Http\Controllers\Corporate\TeamController as CorporateTeamController;
use App\Http\Controllers\Hospital\CatalogueController as HospitalCatalogueController;
use App\Http\Controllers\Hospital\DashboardController as HospitalDashboardController;
use App\Http\Controllers\Hospital\InvoiceController as HospitalInvoiceController;
use App\Http\Controllers\Hospital\ReferralOrderController as HospitalReferralOrderController;
use App\Http\Controllers\Hospital\SpecimenController as HospitalSpecimenController;
use App\Http\Controllers\Hospital\TeamController as HospitalTeamController;
use App\Http\Controllers\Laboratory\CatalogueController as LabCatalogueController;
use App\Http\Controllers\Laboratory\DashboardController as LabDashboardController;
use App\Http\Controllers\Laboratory\QualificationController as LabQualificationController;
use App\Http\Controllers\Laboratory\ReceptionController as LabReceptionController;
use App\Http\Controllers\Laboratory\ResultController as LabResultController;
use App\Http\Controllers\Laboratory\SettlementController as LabSettlementController;
use App\Http\Controllers\Laboratory\TeamController as LabTeamController;
use App\Http\Controllers\Operations\AuditController as OpsAuditController;
use App\Http\Controllers\Operations\CatalogueController as OpsCatalogueController;
use App\Http\Controllers\Operations\DashboardController as OpsDashboardController;
use App\Http\Controllers\Operations\FinanceController as OpsFinanceController;
use App\Http\Controllers\Operations\ImpersonationController as OpsImpersonationController;
use App\Http\Controllers\Operations\LogisticsController as OpsLogisticsController;
use App\Http\Controllers\Operations\OrderController as OpsOrderController;
use App\Http\Controllers\Operations\PartnerController as OpsPartnerController;
use App\Http\Controllers\Operations\QualificationController as OpsQualificationController;
use App\Http\Controllers\Operations\SystemMaintenanceController as OpsSystemMaintenanceController;
use App\Http\Controllers\Operations\TeamController as OpsTeamController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnforceWorkspaceAccess;
use App\Http\Middleware\EnsureLaboratoryHasPathologist;
use App\Http\Middleware\ResolveOrganizationContext;
use Illuminate\Support\Facades\Route;

// Public Website
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Operations Team Invitation Acceptance
    Route::get('/invitations/{token}/accept', [InvitationController::class, 'showAcceptForm'])->name('invitations.accept');
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'processAccept'])->name('invitations.process');

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Multi-Tenant Workspaces
Route::middleware(['auth', ResolveOrganizationContext::class])->group(function () {
    // Exit Impersonation (Available to any active impersonated session)
    Route::match(['get', 'post'], '/impersonate/leave', [OpsImpersonationController::class, 'leave'])->name('ops.impersonate.leave');

    // Institutional User Profile (Accessible across all tenant portals)
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/organization', [ProfileController::class, 'updateOrganization'])->name('profile.organization');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications');

    // Hospital Portal
    Route::prefix('hospital')->name('hospital.')->middleware(EnforceWorkspaceAccess::class.':hospital')->group(function () {
        Route::get('/dashboard', [HospitalDashboardController::class, 'index'])->name('dashboard');
        
        // Referral Orders
        Route::get('/referrals', [HospitalReferralOrderController::class, 'index'])->name('orders.index');
        Route::get('/referrals/create', [HospitalReferralOrderController::class, 'create'])->name('orders.create');
        Route::post('/referrals', [HospitalReferralOrderController::class, 'store'])->name('orders.store');
        Route::get('/referrals/{order}', [HospitalReferralOrderController::class, 'show'])->name('orders.show');

        // Diagnostic Catalogue
        Route::get('/catalogue', [HospitalCatalogueController::class, 'index'])->name('catalogue.index');

        // Specimen Custody & Courier Handover
        Route::get('/specimens', [HospitalSpecimenController::class, 'index'])->name('specimens.index');
        Route::post('/specimens/{specimen}/collect', [HospitalSpecimenController::class, 'recordCollection'])->name('specimens.collect');
        Route::post('/specimens/dispatch', [HospitalSpecimenController::class, 'dispatch'])->name('specimens.dispatch');

        // Invoices & Statements
        Route::get('/invoices', [HospitalInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [HospitalInvoiceController::class, 'show'])->name('invoices.show');

        // Hospital Team & Access Governance
        Route::get('/team', [HospitalTeamController::class, 'index'])->name('team.index');
        Route::post('/team/invite', [HospitalTeamController::class, 'invite'])->name('team.invite');
        Route::patch('/team/{membership}', [HospitalTeamController::class, 'update'])->name('team.update');
        Route::delete('/team/invitations/{invitation}', [HospitalTeamController::class, 'revokeInvitation'])->name('team.invitations.revoke');
        Route::post('/team/invitations/{invitation}/resend', [HospitalTeamController::class, 'resendInvitation'])->name('team.invitations.resend');
    });

    // Laboratory Portal
    Route::prefix('lab')->name('lab.')->middleware([EnforceWorkspaceAccess::class.':lab', EnsureLaboratoryHasPathologist::class])->group(function () {
        Route::get('/dashboard', [LabDashboardController::class, 'index'])->name('dashboard');
        Route::post('/pathologist/setup', [LabDashboardController::class, 'setupPathologist'])->name('pathologist.setup');

        // Specimen Reception & Accessioning
        Route::get('/reception', [LabReceptionController::class, 'index'])->name('reception.index');
        Route::post('/orders/{order}/accept', [LabReceptionController::class, 'acceptOrder'])->name('orders.accept');
        Route::post('/specimens/{specimen}/receive', [LabReceptionController::class, 'receiveSpecimen'])->name('specimens.receive');
        Route::post('/specimens/{specimen}/process', [LabReceptionController::class, 'acceptForProcessing'])->name('specimens.process');
        Route::post('/specimens/{specimen}/reject', [LabReceptionController::class, 'rejectSpecimen'])->name('specimens.reject');

        // Result Entry & Pathologist Release
        Route::get('/results', [LabResultController::class, 'index'])->name('results.index');
        Route::get('/orders/{order}/results/create', [LabResultController::class, 'create'])->name('results.create');
        Route::post('/orders/{order}/results', [LabResultController::class, 'store'])->name('results.store');
        Route::post('/results/{result}/release', [LabResultController::class, 'release'])->name('results.release');

        // Test Catalogue & Capacity
        Route::get('/catalogue', [LabCatalogueController::class, 'index'])->name('catalogue.index');
        Route::post('/catalogue', [LabCatalogueController::class, 'store'])->name('catalogue.store');
        Route::post('/catalogue/initialize', [LabCatalogueController::class, 'initializeFromMaster'])->name('catalogue.initialize');
        Route::patch('/catalogue/{entry}', [LabCatalogueController::class, 'update'])->name('catalogue.update');
        Route::delete('/catalogue/{entry}', [LabCatalogueController::class, 'destroy'])->name('catalogue.destroy');

        // Licensing & Qualifications
        Route::get('/qualifications', [LabQualificationController::class, 'index'])->name('qualifications.index');
        Route::post('/qualifications', [LabQualificationController::class, 'store'])->name('qualifications.store');
        Route::get('/qualifications/{qualification}/document', [LabQualificationController::class, 'document'])->name('qualifications.document');

        // Settlements & Statements
        Route::get('/settlements', [LabSettlementController::class, 'index'])->name('settlements.index');

        // Laboratory Team & Access Governance
        Route::get('/team', [LabTeamController::class, 'index'])->name('team.index');
        Route::post('/team/invite', [LabTeamController::class, 'invite'])->name('team.invite');
        Route::patch('/team/{membership}', [LabTeamController::class, 'update'])->name('team.update');
        Route::delete('/team/invitations/{invitation}', [LabTeamController::class, 'revokeInvitation'])->name('team.invitations.revoke');
        Route::post('/team/invitations/{invitation}/resend', [LabTeamController::class, 'resendInvitation'])->name('team.invitations.resend');
    });

    // Corporate Portal
    Route::prefix('corporate')->name('corporate.')->middleware(EnforceWorkspaceAccess::class.':corporate')->group(function () {
        Route::get('/dashboard', [CorporateDashboardController::class, 'index'])->name('dashboard');
        Route::post('/programs', [CorporateDashboardController::class, 'storeProgram'])->name('programs.store');

        // Employee Rosters
        Route::get('/employees', [CorporateEmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees', [CorporateEmployeeController::class, 'store'])->name('employees.store');

        // Appointment Sessions
        Route::get('/sessions', [CorporateSessionController::class, 'index'])->name('sessions.index');
        Route::post('/sessions', [CorporateSessionController::class, 'store'])->name('sessions.store');

        // Aggregate Health Insights
        Route::get('/insights', [CorporateInsightController::class, 'index'])->name('insights.index');

        // Program Invoices
        Route::get('/invoices', [CorporateInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [CorporateInvoiceController::class, 'show'])->name('invoices.show');

        // Corporate Team & Access Governance
        Route::get('/team', [CorporateTeamController::class, 'index'])->name('team.index');
        Route::post('/team/invite', [CorporateTeamController::class, 'invite'])->name('team.invite');
        Route::patch('/team/{membership}', [CorporateTeamController::class, 'update'])->name('team.update');
        Route::delete('/team/invitations/{invitation}', [CorporateTeamController::class, 'revokeInvitation'])->name('team.invitations.revoke');
        Route::post('/team/invitations/{invitation}/resend', [CorporateTeamController::class, 'resendInvitation'])->name('team.invitations.resend');
    });

    // Central NODIVA Operations Console
    Route::prefix('ops')->name('ops.')->middleware(EnforceWorkspaceAccess::class.':ops')->group(function () {
        Route::get('/dashboard', [OpsDashboardController::class, 'index'])->name('dashboard');
        Route::get('/orders/{order}', [OpsOrderController::class, 'show'])->name('orders.show');
        Route::get('/catalogue', [OpsCatalogueController::class, 'index'])->name('catalogue.index');
        Route::post('/catalogue', [OpsCatalogueController::class, 'store'])->name('catalogue.store');
        Route::match(['put', 'patch'], '/catalogue/{test}', [OpsCatalogueController::class, 'update'])->name('catalogue.update');
        Route::post('/specialties', [OpsCatalogueController::class, 'storeSpecialty'])->name('specialties.store');
        Route::match(['put', 'patch'], '/specialties/{specialty}', [OpsCatalogueController::class, 'updateSpecialty'])->name('specialties.update');
        Route::delete('/specialties/{specialty}', [OpsCatalogueController::class, 'destroySpecialty'])->name('specialties.destroy');

        Route::post('/specimen-types', [OpsCatalogueController::class, 'storeSpecimenType'])->name('specimen-types.store');
        Route::match(['put', 'patch'], '/specimen-types/{specimenType}', [OpsCatalogueController::class, 'updateSpecimenType'])->name('specimen-types.update');
        Route::delete('/specimen-types/{specimenType}', [OpsCatalogueController::class, 'destroySpecimenType'])->name('specimen-types.destroy');
        Route::get('/partners', [OpsPartnerController::class, 'index'])->name('partners.index');
        Route::delete('/partners/delete-all', [OpsPartnerController::class, 'destroyAll'])->name('partners.destroyAll');
        Route::delete('/partners/{partner}', [OpsPartnerController::class, 'destroy'])->name('partners.destroy');
        Route::delete('/partners/{partner}/members/{membership}', [OpsPartnerController::class, 'destroyMember'])->name('partners.destroyMember');
        Route::patch('/partners/{partner}/status', [OpsPartnerController::class, 'updateStatus'])->name('partners.status');
        Route::delete('/users/delete-all', [OpsPartnerController::class, 'destroyAllUsers'])->name('users.destroyAll');
        Route::delete('/users/{user}', [OpsPartnerController::class, 'destroyUser'])->name('users.destroy');

        // Chief Technical Officer Impersonation
        Route::post('/impersonate/user/{user}', [OpsImpersonationController::class, 'impersonateUser'])->name('impersonate.user');
        Route::post('/impersonate/organization/{partner}', [OpsImpersonationController::class, 'impersonateOrganization'])->name('impersonate.organization');
        Route::get('/qualifications', [OpsQualificationController::class, 'index'])->name('qualifications.index');
        Route::post('/qualifications/{qualification}/verify', [OpsQualificationController::class, 'verify'])->name('qualifications.verify');
        Route::get('/qualifications/{qualification}/document', [OpsQualificationController::class, 'document'])->name('qualifications.document');
        Route::post('/qualifications/categories', [OpsQualificationController::class, 'storeCategory'])->name('qualifications.categories.store');
        Route::patch('/qualifications/categories/{category}', [OpsQualificationController::class, 'updateCategory'])->name('qualifications.categories.update');
        Route::post('/qualifications/categories/{category}/toggle-compulsory', [OpsQualificationController::class, 'toggleCompulsory'])->name('qualifications.categories.toggle-compulsory');
        Route::post('/qualifications/categories/{category}/toggle-active', [OpsQualificationController::class, 'toggleActive'])->name('qualifications.categories.toggle-active');
        Route::get('/logistics', [OpsLogisticsController::class, 'index'])->name('logistics.index');
        Route::get('/finance', [OpsFinanceController::class, 'index'])->name('finance.index');
        Route::post('/finance/reconcile', [OpsFinanceController::class, 'runReconciliation'])->name('finance.reconcile');
        Route::post('/finance/settings', [OpsFinanceController::class, 'updateSettings'])->name('finance.settings');
        Route::get('/audit', [OpsAuditController::class, 'index'])->name('audit.index');
        Route::post('/system/purge-database', [OpsSystemMaintenanceController::class, 'purgeDatabase'])->name('system.purge');

        // Operations Team & Access Governance
        Route::get('/team', [OpsTeamController::class, 'index'])->name('team.index');
        Route::post('/team/invite', [OpsTeamController::class, 'invite'])->name('team.invite');
        Route::patch('/team/{membership}/permissions', [OpsTeamController::class, 'updatePermissions'])->name('team.permissions');
        Route::delete('/team/{membership}', [OpsTeamController::class, 'destroy'])->name('team.destroy');
        Route::delete('/team/invitations/{invitation}', [OpsTeamController::class, 'revokeInvitation'])->name('team.invitations.revoke');
        Route::post('/team/invitations/{invitation}/resend', [OpsTeamController::class, 'resendInvitation'])->name('team.invitations.resend');
        Route::post('/team/roles', [OpsTeamController::class, 'storeRole'])->name('team.roles.store');
    });
});
