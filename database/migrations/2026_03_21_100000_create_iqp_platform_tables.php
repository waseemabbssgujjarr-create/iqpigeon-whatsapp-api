<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 32)->default('pending');
            $table->string('provisioning_status', 32)->default('registered');
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'provisioning_status']);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('stripe_price_id')->nullable()->index();
            $table->unsignedInteger('price_cents')->default(0);
            $table->char('currency', 3)->default('usd');
            $table->string('interval', 16)->default('month');
            $table->json('feature_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'stripe_status']);
        });

        Schema::create('stripe_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique();
            $table->string('type');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'processed_at']);
        });

        Schema::create('api_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description');
            $table->string('group')->nullable();
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Primary');
            $table->string('prefix', 32)->index();
            $table->string('key_hash');
            $table->string('environment', 16)->default('live');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'revoked_at']);
        });

        Schema::create('api_key_scope', function (Blueprint $table) {
            $table->foreignId('api_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_scope_id')->constrained('api_scopes')->cascadeOnDelete();

            $table->primary(['api_key_id', 'api_scope_id']);
        });

        Schema::create('whatsapp_connections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('external_ref')->nullable();
            $table->string('waba_id')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('display_phone_number')->nullable();
            $table->string('meta_business_id')->nullable();
            $table->string('connection_status', 32)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();

            $table->unique(['partner_id', 'phone_number_id']);
            $table->index(['partner_id', 'connection_status']);
        });

        Schema::create('whatsapp_connection_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_connection_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('waba_id')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('embedded_signup_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_connection_id')->constrained()->cascadeOnDelete();
            $table->string('state_token_hash', 128);
            $table->string('status', 32)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['state_token_hash', 'status']);
            $table->index(['whatsapp_connection_id', 'status']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_connection_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 16);
            $table->string('wa_message_id')->nullable()->index();
            $table->string('status', 32)->default('queued');
            $table->string('from_number', 32)->nullable();
            $table->string('to_number', 32)->nullable();
            $table->string('message_type', 32)->default('text');
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'created_at']);
            $table->index(['whatsapp_connection_id', 'status']);
        });

        Schema::create('api_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key', 128);
            $table->string('request_method', 16);
            $table->string('request_path', 512);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['partner_id', 'idempotency_key']);
            $table->index('expires_at');
        });

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->text('secret')->nullable();
            $table->json('events')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['partner_id', 'is_active']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('webhook_endpoint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->json('payload');
            $table->string('status', 32)->default('pending');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_retry_at']);
            $table->index(['partner_id', 'created_at']);
        });

        Schema::create('api_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 64)->unique();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 16);
            $table->string('path', 512);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'created_at']);
        });

        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 64);
            $table->unsignedBigInteger('quantity')->default(0);
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'metric', 'period_start']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('system_events', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32);
            $table->string('event_type');
            $table->string('external_id')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index(['source', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_events');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('api_requests');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('api_idempotency_keys');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('embedded_signup_sessions');
        Schema::dropIfExists('whatsapp_connection_credentials');
        Schema::dropIfExists('whatsapp_connections');
        Schema::dropIfExists('api_key_scope');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('api_scopes');
        Schema::dropIfExists('stripe_events');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('partners');
    }
};
