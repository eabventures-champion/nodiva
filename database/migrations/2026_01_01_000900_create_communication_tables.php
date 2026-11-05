<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_threads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 24)->unique();
            $table->string('subject');
            $table->string('category', 32)->default('general');
            $table->string('priority', 20)->default('normal'); // normal, high, urgent
            $table->string('recipient_type', 32)->default('direct'); // direct, all_hospitals, all_laboratories, all_corporates, all_partners
            $table->foreignId('sender_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('status', 20)->default('open'); // open, closed
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['recipient_type', 'status']);
            $table->index(['recipient_organization_id', 'status']);
            $table->index(['sender_organization_id', 'status']);
        });

        Schema::create('communication_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained('communication_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
        });

        Schema::create('communication_thread_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained('communication_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('last_read_message_id')->nullable()->constrained('communication_messages')->nullOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->unique(['thread_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_thread_reads');
        Schema::dropIfExists('communication_messages');
        Schema::dropIfExists('communication_threads');
    }
};
