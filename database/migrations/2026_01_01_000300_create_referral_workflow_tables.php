<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patients, consent, referral orders, assignments, specimens, chain of custody, results.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); // owning hospital
            $table->string('reference', 24)->unique(); // pseudonymous patient-safe identifier
            $table->text('medical_record_number')->nullable(); // encrypted
            $table->string('mrn_hash', 64)->nullable();       // blind index for duplicate detection
            $table->text('first_name');   // encrypted
            $table->text('last_name');    // encrypted
            $table->text('date_of_birth'); // encrypted
            $table->string('sex', 10);     // female | male | other | unknown
            $table->text('phone')->nullable(); // encrypted
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'mrn_hash']);
        });

        Schema::create('patient_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 40); // external_referral_testing | result_sharing
            $table->string('method', 20);  // written | verbal_witnessed | electronic
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['patient_id', 'purpose']);
        });

        Schema::create('referral_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 24)->unique();
            $table->foreignId('requesting_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('organization_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('laboratory_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 32)->default('draft')->index();
            $table->string('priority', 12)->default('routine'); // routine | urgent
            $table->string('collection_method', 24);            // courier | laboratory_collection | walk_in
            $table->text('clinical_notes')->nullable();         // encrypted
            $table->string('clinical_indication')->nullable();  // non-identifying, coded summary
            $table->char('currency', 3);
            $table->string('selection_mode', 20)->nullable();   // recommended | manual | override
            $table->json('selection_rationale')->nullable();
            $table->text('override_reason')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('specimen_received_at')->nullable();
            $table->timestamp('result_released_at')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();
            $table->index(['requesting_organization_id', 'status']);
            $table->index(['laboratory_id', 'status']);
        });

        Schema::create('referral_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnostic_test_id')->constrained()->restrictOnDelete();
            $table->foreignId('test_catalogue_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quoted_price', 15, 2)->nullable();
            $table->decimal('agreed_price', 15, 2)->nullable();
            $table->unsignedInteger('turnaround_hours')->nullable();
            $table->timestamps();
            $table->unique(['referral_order_id', 'diagnostic_test_id']);
        });

        Schema::create('order_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('laboratory_id')->constrained('organizations')->restrictOnDelete();
            $table->string('status', 20)->index(); // offered | accepted | rejected | clarification_requested | withdrawn
            $table->decimal('match_score', 6, 2)->nullable();
            $table->json('rationale')->nullable();
            $table->string('selection_mode', 20); // recommended | manual | override | reassignment
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('order_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['referral_order_id', 'created_at']);
        });

        Schema::create('specimens', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('identifier', 24)->unique(); // barcode / QR payload
            $table->foreignId('referral_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specimen_type_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('pending_collection')->index();
            $table->unsignedSmallInteger('sequence')->default(1); // recollection count
            $table->foreignId('custodian_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('referral_order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // courier | laboratory_collection | walk_in
            $table->string('carrier_name')->nullable();
            $table->string('tracking_reference', 64)->nullable();
            $table->string('pickup_location')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('status', 20)->default('scheduled')->index(); // scheduled | in_transit | delivered | failed | cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('chain_of_custody_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')->constrained()->cascadeOnDelete();
            $table->string('event', 20);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('dispatch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->string('handed_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['specimen_id', 'occurred_at']);
        });

        Schema::create('diagnostic_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('referral_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->foreignId('supersedes_id')->nullable()->constrained('diagnostic_results')->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->text('interpretation')->nullable(); // encrypted
            $table->boolean('is_critical')->default(false);
            $table->text('amendment_reason')->nullable();
            $table->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->unique(['referral_order_id', 'version']);
        });

        Schema::create('diagnostic_result_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostic_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referral_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('analyte');
            $table->string('loinc_code', 20)->nullable();
            $table->text('value'); // encrypted
            $table->string('unit', 32)->nullable();
            $table->string('reference_range', 64)->nullable();
            $table->string('flag', 12)->default('normal'); // normal | low | high | abnormal | critical
            $table->timestamps();
        });

        Schema::create('result_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('diagnostic_result_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 20);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('critical_result_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostic_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('acknowledged_at');
            $table->unique(['diagnostic_result_id', 'organization_id'], 'crit_res_ack_res_org_uniq');
        });
    }

    public function down(): void
    {
        foreach (['critical_result_acknowledgements', 'result_documents', 'diagnostic_result_items', 'diagnostic_results', 'chain_of_custody_events', 'dispatches', 'specimens', 'order_status_events', 'order_assignments', 'referral_order_items', 'referral_orders', 'patient_consents', 'patients'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
