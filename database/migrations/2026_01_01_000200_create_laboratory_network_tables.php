<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laboratory network: profiles, qualifications, catalogue, pricing, capacity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->json('operating_hours')->nullable();
            $table->unsignedInteger('daily_capacity')->default(0);
            $table->boolean('is_accepting_orders')->default(false);
            $table->boolean('offers_collection')->default(false);
            $table->string('restriction_note')->nullable();
            $table->timestamp('restricted_until')->nullable();
            $table->decimal('reliability_score', 5, 2)->nullable(); // 0-100, computed from history
            $table->timestamps();
        });

        Schema::create('laboratory_service_region', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_region_id')->constrained()->cascadeOnDelete();
            $table->primary(['organization_id', 'service_region_id']);
        });

        Schema::create('laboratory_qualifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);          // operating_license | accreditation | quality_assurance | professional_registration
            $table->string('issuing_body');
            $table->string('reference_number', 100);
            $table->text('scope')->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->string('status', 20)->default('pending')->index(); // pending | verified | rejected | expired
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('qualification_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_qualification_id')->constrained()->cascadeOnDelete();
            $table->string('action', 20); // submitted | verified | rejected | expired | resubmitted
            $table->text('notes')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('diagnostic_specialties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('required_qualification_type', 40)->nullable(); // e.g. accreditation required for molecular
            $table->timestamps();
        });

        Schema::create('specimen_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('container')->nullable();
            $table->string('transport_requirements')->nullable(); // ambient | refrigerated | frozen
            $table->unsignedInteger('stability_hours')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnostic_tests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->foreignId('diagnostic_specialty_id')->constrained()->restrictOnDelete();
            $table->foreignId('specimen_type_id')->constrained()->restrictOnDelete();
            $table->string('loinc_code', 20)->nullable(); // interoperability-ready, not a conformance claim
            $table->text('description')->nullable();
            $table->text('patient_preparation')->nullable();
            $table->boolean('requires_clinical_notes')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->fullText(['name', 'code']);
        });

        Schema::create('test_catalogue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); // laboratory
            $table->foreignId('diagnostic_test_id')->constrained()->cascadeOnDelete();
            $table->string('lab_test_code', 40)->nullable();
            $table->decimal('price', 15, 2);
            $table->char('currency', 3);
            $table->unsignedInteger('turnaround_hours');
            $table->unsignedInteger('daily_capacity')->nullable();
            $table->boolean('is_available')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'diagnostic_test_id']);
        });

        Schema::create('laboratory_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('client_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('diagnostic_test_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('fixed_price', 15, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->index(['laboratory_id', 'client_organization_id'], 'lab_pricing_lab_client_idx');
        });
    }

    public function down(): void
    {
        foreach (['laboratory_pricing_rules', 'test_catalogue_entries', 'diagnostic_tests', 'specimen_types', 'diagnostic_specialties', 'qualification_verifications', 'laboratory_qualifications', 'laboratory_service_region', 'laboratory_profiles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
