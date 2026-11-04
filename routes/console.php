<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('nodiva:check-users', function () {
    $users = \App\Models\User::all();
    $this->info("Total users: " . $users->count());
    foreach ($users as $u) {
        $check = \Illuminate\Support\Facades\Hash::check('NodivaSecure2026!', $u->password);
        $membership = $u->memberships()->first();
        $org = $membership?->organization;
        $this->line("User: {$u->email} | Org: {$org?->name} ({$org?->type?->value}) | Password matches: " . ($check ? 'YES' : 'NO'));
    }
});

Artisan::command('nodiva:reset-passwords', function () {
    $password = \Illuminate\Support\Facades\Hash::make('NodivaSecure2026!');
    foreach (\App\Models\User::all() as $u) {
        $u->password = $password;
        $u->save();
        $this->info("Password updated for {$u->email}");
    }
});

Artisan::command('nodiva:seed-lab-admin', function () {
    $lab = \App\Models\Organization::where('name', 'SynLab Diagnostics West Africa')
        ->orWhere('email', 'intake@synlab-gh.example')
        ->firstOrFail();

    $role = \App\Models\Role::where('key', 'lab_admin')->firstOrFail();
    $defaultPassword = \Illuminate\Support\Facades\Hash::make('NodivaSecure2026!');

    $labAdmin = \App\Models\User::firstOrCreate(['email' => 'admin@synlab-gh.example'], [
        'name' => 'Dr. Irene Mensah',
        'password' => $defaultPassword,
        'job_title' => 'Laboratory Administrator & General Manager',
    ]);

    $membership = \App\Models\OrganizationMembership::firstOrCreate([
        'user_id' => $labAdmin->id,
        'organization_id' => $lab->id,
    ], [
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    $membership->role_id = $role->id;
    $membership->status = 'active';
    $membership->save();

    $labAdmin->current_membership_id = $membership->id;
    $labAdmin->save();

    $this->info("Laboratory Administrator created successfully: admin@synlab-gh.example (Dr. Irene Mensah)");
});

Artisan::command('nodiva:seed-hospital-admin', function () {
    $hospital = \App\Models\Organization::where('name', 'Ridge Specialist Hospital')
        ->orWhere('email', 'intake@ridgespecialist.example')
        ->orWhere('email', 'like', '%ridgespecialist%')
        ->firstOrFail();

    $role = \App\Models\Role::where('key', 'hospital_admin')->firstOrFail();
    $defaultPassword = \Illuminate\Support\Facades\Hash::make('NodivaSecure2026!');

    $admin = \App\Models\User::firstOrCreate(['email' => 'admin@ridgespecialist.com'], [
        'name' => 'Dr. Anthony Boateng',
        'password' => $defaultPassword,
        'job_title' => 'Chief Medical Officer & Hospital Administrator',
    ]);

    $membership = \App\Models\OrganizationMembership::firstOrCreate([
        'user_id' => $admin->id,
        'organization_id' => $hospital->id,
    ], [
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    $membership->role_id = $role->id;
    $membership->status = 'active';
    $membership->save();

    $admin->current_membership_id = $membership->id;
    $admin->save();

    $this->info("Hospital Administrator created successfully: admin@ridgespecialist.com (Dr. Anthony Boateng)");
});

Artisan::command('nodiva:seed-corporate-admin', function () {
    $corp = \App\Models\Organization::where('name', 'Tullow Oil Ghana Ltd')
        ->orWhere('email', 'wellness@tullow.example')
        ->orWhere('email', 'like', '%tullow%')
        ->firstOrFail();

    $role = \App\Models\Role::where('key', 'corporate_admin')->firstOrFail();
    $defaultPassword = \Illuminate\Support\Facades\Hash::make('NodivaSecure2026!');

    $admin = \App\Models\User::firstOrCreate(['email' => 'admin@tullow.example'], [
        'name' => 'Abena Nyarko',
        'password' => $defaultPassword,
        'job_title' => 'Corporate Wellness Director & Occupational Administrator',
    ]);

    $membership = \App\Models\OrganizationMembership::firstOrCreate([
        'user_id' => $admin->id,
        'organization_id' => $corp->id,
    ], [
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    $membership->role_id = $role->id;
    $membership->status = 'active';
    $membership->save();

    $admin->current_membership_id = $membership->id;
    $admin->save();

    $this->info("Corporate Administrator created successfully: admin@tullow.example (Abena Nyarko)");
});
