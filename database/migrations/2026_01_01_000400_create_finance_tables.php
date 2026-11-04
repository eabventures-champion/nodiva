<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finance: fee rules, order ledger, invoicing, payments, credits, refunds, settlements, reconciliation.
 * All monetary values are DECIMAL(15,2) with explicit currency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_fee_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scope', 20)->index(); // global | specialty | organization | contract
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('diagnostic_specialty_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('rate_percent', 5, 2)->default(0);
            $table->decimal('flat_fee', 15, 2)->default(0);
            $table->char('currency', 3)->default('GHS');
            $table->string('fee_bearer', 20)->default('laboratory'); // laboratory (deducted from payable) | institution (added to invoice)
            $table->unsignedSmallInteger('priority')->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('currency', 3);
            $table->decimal('service_value', 15, 2);      // diagnostic GMV (agreed lab prices)
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('logistics_charge', 15, 2)->default(0);
            $table->foreignId('platform_fee_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('platform_fee_rate', 5, 2)->default(0);
            $table->decimal('platform_fee', 15, 2)->default(0);
            $table->string('fee_bearer', 20);
            $table->string('fee_basis')->nullable();        // human-readable basis snapshot
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('institution_total', 15, 2);   // amount billed to the requesting institution
            $table->decimal('laboratory_payable', 15, 2);
            $table->decimal('platform_revenue', 15, 2);    // fee + logistics (never the GMV)
            $table->string('status', 20)->default('quoted')->index(); // quoted | locked | invoiced | settled
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('laboratory_settlement_id')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number', 32)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete(); // billed party
            $table->foreignId('referral_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('screening_program_id')->nullable();
            $table->string('status', 20)->default('issued')->index(); // draft | issued | partially_paid | paid | void
            $table->char('currency', 3);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_credited', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2);
            $table->date('issued_on');
            $table->date('due_on');
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('category', 24); // diagnostic_service | platform_fee | logistics | discount | tax | program_fee
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_amount', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 40)->unique(); // immutable transaction reference
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->char('currency', 3);
            $table->decimal('amount', 15, 2);
            $table->decimal('unallocated_amount', 15, 2);
            $table->string('method', 24); // bank_transfer | mobile_money | cheque | card_gateway
            $table->string('provider', 40); // manual | <gateway>
            $table->string('provider_reference', 100)->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending | confirmed | failed
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->unique(['provider', 'provider_reference']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending | completed | failed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('laboratory_settlements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 32)->unique();
            $table->foreignId('laboratory_id')->constrained('organizations')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->char('currency', 3);
            $table->decimal('gross_service_value', 15, 2);
            $table->decimal('platform_fees', 15, 2);
            $table->decimal('net_payable', 15, 2);
            $table->unsignedInteger('order_count');
            $table->string('status', 20)->default('draft'); // draft | approved | paid
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('order_financials', function (Blueprint $table) {
            $table->foreign('laboratory_settlement_id')->references('id')->on('laboratory_settlements')->nullOnDelete();
        });

        Schema::create('reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->char('currency', 3);
            $table->unsignedInteger('orders_checked')->default(0);
            $table->unsignedInteger('discrepancy_count')->default(0);
            $table->json('totals')->nullable();
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referral_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40); // not_invoiced | invoice_mismatch | unpaid | partially_paid | overpaid | unsettled | matched
            $table->decimal('expected_amount', 15, 2)->default(0);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->morphs('adjustable');
            $table->string('type', 24); // credit_note | refund | write_off | correction
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->text('reason');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::table('order_financials', fn (Blueprint $t) => $t->dropForeign(['laboratory_settlement_id']));
        foreach (['financial_adjustments', 'reconciliation_items', 'reconciliation_runs', 'laboratory_settlements', 'refunds', 'credit_notes', 'payment_allocations', 'payments', 'invoice_items', 'invoices', 'order_financials', 'platform_fee_rules'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
