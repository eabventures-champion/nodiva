<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('organization_memberships', 'permissions')) {
            Schema::table('organization_memberships', function (Blueprint $table) {
                $table->json('permissions')->nullable()->after('status');
            });
        }

        if (! Schema::hasTable('organization_invitations')) {
            Schema::create('organization_invitations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->string('email');
                $table->string('name')->nullable();
                $table->string('job_title')->nullable();
                $table->foreignId('role_id')->constrained()->restrictOnDelete();
                $table->json('permissions')->nullable();
                $table->string('token', 64)->unique();
                $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
                $table->string('status', 20)->default('pending')->index(); // pending | accepted | revoked | expired
                $table->timestamp('expires_at');
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'status']);
                $table->index(['email', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');

        if (Schema::hasColumn('organization_memberships', 'permissions')) {
            Schema::table('organization_memberships', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }
    }
};
