<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identity, organisations, memberships and RBAC.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_regions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->char('country_code', 2)->index();
            $table->boolean('is_launch_active')->default(false); // configurable phased expansion
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 20)->index();   // hospital | laboratory | corporate | platform
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('registration_number', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('address_line')->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('service_region_id')->nullable()->constrained()->nullOnDelete();
            $table->char('country_code', 2)->default('GH');
            $table->char('currency', 3)->default('GHS');
            $table->string('status', 20)->default('pending')->index();
            $table->text('status_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'status']);
        });

        Schema::create('organization_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->string('address_line')->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('service_region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 32)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name');
            $table->string('group', 40)->index();
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('name');
            $table->string('organization_type', 20)->index();
            $table->text('description')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('organization_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('active')->index(); // active | suspended
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'organization_id']);
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('value');
            $table->string('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 40)->unique();
            $table->string('title');
            $table->string('contract_type', 40); // referral_services | laboratory_partnership | corporate_program | software
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->json('terms')->nullable();   // structured commercial terms (data-driven, not hardcoded)
            $table->string('document_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['contracts', 'system_settings', 'organization_memberships', 'permission_role', 'roles', 'permissions', 'departments', 'organization_branches', 'organizations', 'service_regions'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
