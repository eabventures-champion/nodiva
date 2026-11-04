<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Domain\Organizations\Enums\OrganizationStatus;
use App\Domain\Organizations\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Mail\AccessRequestConfirmationMail;
use App\Mail\NewAccessRequestNotification;
use App\Models\LaboratoryProfile;
use App\Models\Organization;
use App\Models\OrganizationBranch;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\ServiceRegion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function showRegistrationForm(Request $request)
    {
        $requestedType = $request->query('type', 'hospital');
        if (! in_array($requestedType, ['hospital', 'laboratory', 'corporate'], true)) {
            $requestedType = 'hospital';
        }

        $serviceRegions = ServiceRegion::where('is_launch_active', true)->orderBy('name')->get();

        return view('auth.register', [
            'initialType' => $requestedType,
            'serviceRegions' => $serviceRegions,
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:hospital,laboratory,corporate'],
            'organization_name' => ['required', 'string', 'max:150'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'organization_email' => ['required', 'email', 'max:150', 'unique:organizations,email'],
            'organization_phone' => ['required', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:100'],
            'service_region_id' => ['nullable', 'exists:service_regions,id'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:100'],
            'job_title' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'consent_data_protection' => ['required', 'accepted'],
        ]);

        $type = $validated['type'];

        $org = DB::transaction(function () use ($validated, $type) {
            // 1. Create Organization
            $org = new Organization([
                'name' => $validated['organization_name'],
                'registration_number' => $validated['registration_number'] ?? null,
                'email' => $validated['organization_email'],
                'phone' => $validated['organization_phone'],
                'address_line' => $validated['address_line'] ?? null,
                'city' => $validated['city'],
                'service_region_id' => $validated['service_region_id'] ?? null,
                'currency' => 'GHS',
                'country_code' => 'GH',
            ]);

            $org->type = match ($type) {
                'hospital' => OrganizationType::Hospital,
                'laboratory' => OrganizationType::Laboratory,
                'corporate' => OrganizationType::Corporate,
            };
            // Set status to pending verification for all newly registered network institutions
            $org->status = OrganizationStatus::Pending;
            $org->approved_at = null;
            $org->save();

            // 2. Setup Type-specific records
            if ($type === 'hospital') {
                $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $org->name), 0, 6)) . '-01';
                OrganizationBranch::create([
                    'organization_id' => $org->id,
                    'name' => 'Main Clinical Facility',
                    'code' => $code,
                    'city' => $org->city,
                    'service_region_id' => $org->service_region_id,
                    'is_primary' => true,
                ]);
            } elseif ($type === 'laboratory') {
                LaboratoryProfile::create([
                    'organization_id' => $org->id,
                    'description' => 'Accredited testing facility on NODIVA Diagnostic Network.',
                    'daily_capacity' => 500,
                    'is_accepting_orders' => true,
                    'offers_collection' => true,
                    'reliability_score' => '99.00',
                ]);
            }

            // 3. Create Admin User
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'job_title' => $validated['job_title'],
            ]);

            // 4. Assign Admin Role
            $roleKey = match ($type) {
                'hospital' => 'hospital_admin',
                'laboratory' => 'lab_admin',
                'corporate' => 'corporate_admin',
            };
            $role = Role::where('key', $roleKey)->first();

            $membership = OrganizationMembership::create([
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'role_id' => $role?->id,
                'status' => 'active',
            ]);

            $user->current_membership_id = $membership->id;
            $user->save();

            // 5. Audit Log
            $actor = Actor::for($user, $org);
            $this->audit->log('organization.registered', $org, [
                'type' => $type,
                'status' => 'pending',
                'admin_email' => $user->email,
            ], 'success', $actor);

            return ['organization' => $org, 'user' => $user];
        });

        $orgModel = $org['organization'];
        $userModel = $org['user'];

        // Send confirmation receipt email to the applicant
        try {
            $mail = Mail::to($userModel->email);
            if (! empty($orgModel->email) && strtolower($orgModel->email) !== strtolower($userModel->email)) {
                $mail->cc($orgModel->email);
            }
            $mail->send(new AccessRequestConfirmationMail($orgModel, $userModel));
        } catch (\Throwable $e) {
            Log::warning("Failed sending access request confirmation email to applicant: {$e->getMessage()}");
        }

        // Send email notification to Platform Operations Console
        try {
            $opsEmails = User::whereHas('memberships.organization', function ($q) {
                $q->where('type', OrganizationType::Platform);
            })->pluck('email')->all();

            if (empty($opsEmails)) {
                $opsEmails = [config('mail.from.address', 'ops@nodiva.com')];
            }

            Mail::to($opsEmails)->send(new NewAccessRequestNotification($orgModel, $userModel));
        } catch (\Throwable $e) {
            Log::warning("Failed sending new access request email notification to ops: {$e->getMessage()}");
        }

        // Do not activate or log into operational workspace; render the pending confirmation view
        return response()->view('auth.pending-approval', [
            'organization' => $orgModel,
            'applicant' => $userModel,
        ]);
    }
}
