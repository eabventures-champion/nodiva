<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corporate screening programmes and platform operations (audit, support, integrations, idempotency).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screening_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('purpose', 40); // pre_employment | annual_medical | occupational_health | risk_screening
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('screening_package_tests', function (Blueprint $table) {
            $table->foreignId('screening_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnostic_test_id')->constrained()->cascadeOnDelete();
            $table->primary(['screening_package_id', 'diagnostic_test_id']);
        });

        Schema::create('screening_programs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 24)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete(); // corporate client
            $table->foreignId('screening_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('laboratory_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('name');
            $table->string('purpose', 40);
            $table->string('status', 20)->default('draft')->index(); // draft | active | completed | cancelled
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('budget', 15, 2)->nullable();
            $table->char('currency', 3);
            $table->string('pricing_model', 20)->default('per_participant'); // per_participant | fixed
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->text('eligibility_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('screening_program_tests', function (Blueprint $table) {
            $table->foreignId('screening_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnostic_test_id')->constrained()->cascadeOnDelete();
            $table->primary(['screening_program_id', 'diagnostic_test_id']);
        });

        Schema::create('employee_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number', 40);
            $table->text('full_name'); // encrypted
            $table->text('email')->nullable(); // encrypted
            $table->text('phone')->nullable(); // encrypted
            $table->string('sex', 10)->nullable();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->string('department')->nullable();
            $table->foreignId('organization_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'employee_number']);
        });

        Schema::create('employee_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('screening_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_name');
            $table->string('status', 20); // validated | imported | failed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->json('errors')->nullable(); // row-level validation errors (no clinical data)
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('program_participants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('screening_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_record_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('invited')->index(); // invited | scheduled | attended | collected | completed | no_show | withdrawn
            $table->boolean('consent_to_screening')->default(false);
            $table->boolean('consent_to_employer_results')->default(false); // explicit, separate consent
            $table->timestamp('consent_recorded_at')->nullable();
            $table->string('outcome', 20)->nullable(); // fit | fit_with_restrictions | unfit | referred — summary, not clinical values
            $table->timestamps();
            $table->unique(['screening_program_id', 'employee_record_id'], 'prog_part_prog_emp_uniq');
        });

        Schema::create('collection_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('screening_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location');
            $table->string('mode', 20); // onsite_mobile | facility
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('booked_count')->default(0);
            $table->timestamps();
        });

        Schema::create('screening_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_participant_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('booked'); // booked | attended | no_show | cancelled
            $table->timestamps();
            $table->unique(['collection_session_id', 'program_participant_id'], 'screen_appt_sess_part_uniq');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('screening_program_id')->references('id')->on('screening_programs')->nullOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('action', 80)->index();
            $table->string('auditable_type', 80)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('outcome', 20)->default('success'); // success | denied | failed
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('context')->nullable(); // minimal, redacted
            $table->char('previous_hash', 64)->nullable();
            $table->char('hash', 64);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 24)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referral_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 24); // support | dispute | complaint | incident
            $table->string('severity', 12)->default('normal'); // low | normal | high | critical
            $table->string('subject');
            $table->text('description');
            $table->string('status', 20)->default('open')->index(); // open | investigating | resolved | closed
            $table->text('resolution')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_credentials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->char('token_prefix', 8)->index();
            $table->char('token_hash', 64)->unique(); // SHA-256; plaintext shown once only
            $table->json('scopes');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('idempotency_records', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64);
            $table->string('scope', 60);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('resource_type', 80)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['scope', 'key']);
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('event_id', 100);
            $table->string('type', 80);
            $table->json('payload');
            $table->string('status', 20)->default('received');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $t) => $t->dropForeign(['screening_program_id']));
        foreach (['notifications', 'webhook_events', 'idempotency_records', 'integration_credentials', 'support_tickets', 'audit_logs', 'screening_appointments', 'collection_sessions', 'program_participants', 'employee_imports', 'employee_records', 'screening_program_tests', 'screening_programs', 'screening_package_tests', 'screening_packages'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
