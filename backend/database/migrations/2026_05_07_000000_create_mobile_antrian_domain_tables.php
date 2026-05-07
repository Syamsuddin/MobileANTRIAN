<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('operator')->after('password')->index();
            $table->boolean('is_active')->default(true)->after('role')->index();
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('mobile');
            $table->string('token_hash', 64)->unique();
            $table->json('device')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('prefix', 8);
            $table->string('color', 16)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('counter_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['counter_id', 'service_id']);
        });

        Schema::create('counter_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['user_id', 'is_active', 'created_at']);
        });

        Schema::create('daily_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->date('sequence_date');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['service_id', 'sequence_date']);
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no');
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->date('ticket_date')->index();
            $table->string('status')->default('waiting')->index();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['service_id', 'ticket_date', 'status', 'created_at', 'id']);
            $table->unique(['ticket_no', 'ticket_date']);
        });

        Schema::create('queue_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('call_no')->default(1);
            $table->string('event_type')->index();
            $table->timestamp('called_at');
            $table->string('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['operator_id', 'called_at']);
            $table->index(['counter_id', 'event_type', 'called_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('request_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('queue_calls');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('daily_sequences');
        Schema::dropIfExists('counter_assignments');
        Schema::dropIfExists('counter_services');
        Schema::dropIfExists('counters');
        Schema::dropIfExists('services');
        Schema::dropIfExists('api_tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'last_login_at']);
        });
    }
};
