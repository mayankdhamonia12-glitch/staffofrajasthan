<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_paise');
            $table->char('currency', 3)->default('INR');
            $table->unsignedSmallInteger('job_posting_credits')->nullable();
            $table->unsignedSmallInteger('listing_validity_days')->nullable();
            $table->unsignedSmallInteger('candidate_access_credits')->nullable();
            $table->unsignedSmallInteger('entitlement_validity_days')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->index(['type', 'is_active', 'display_order'], 'packages_catalog_index');
        });

        Schema::create('package_purchases', function (Blueprint $table): void {
            $table->id();
            $table->uuid('purchase_reference')->unique();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->json('package_snapshot');
            $table->unsignedBigInteger('amount_paise');
            $table->char('currency', 3)->default('INR');
            $table->string('status', 24)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['buyer_user_id', 'status', 'created_at'], 'package_purchases_buyer_status_index');
        });

        Schema::create('package_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_purchase_id')->constrained('package_purchases')->cascadeOnDelete();
            $table->string('provider', 32)->default('razorpay');
            $table->string('provider_order_id', 80)->nullable()->unique();
            $table->string('provider_payment_id', 80)->nullable()->unique();
            $table->unsignedBigInteger('amount_paise');
            $table->char('currency', 3)->default('INR');
            $table->string('status', 24)->default('created')->index();
            $table->string('failure_code', 100)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            $table->index(['package_purchase_id', 'status']);
        });

        Schema::create('package_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_purchase_id')->unique()->constrained('package_purchases')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->string('type', 32);
            $table->unsignedInteger('credits_granted');
            $table->unsignedInteger('credits_remaining');
            $table->json('package_snapshot');
            $table->timestamp('starts_at');
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type', 'valid_until'], 'package_entitlements_lookup_index');
        });

        Schema::create('job_posting_uses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_entitlement_id')->constrained('package_entitlements')->restrictOnDelete();
            $table->foreignId('job_id')->unique()->constrained('jobs')->cascadeOnDelete();
            $table->foreignId('employer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consumed_at');
            $table->timestamps();
            $table->index(['employer_user_id', 'consumed_at']);
        });

        Schema::create('candidate_profile_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
            $table->foreignId('package_entitlement_id')->nullable()->constrained('package_entitlements')->nullOnDelete();
            $table->timestamp('granted_at');
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
            $table->unique(['employer_user_id', 'candidate_profile_id'], 'candidate_profile_access_unique');
            $table->index(['employer_user_id', 'valid_until']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id', 120)->unique();
            $table->string('event_type', 80);
            $table->char('payload_hash', 64);
            $table->string('status', 24)->default('processing')->index();
            $table->string('failure_code', 100)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('jobs', function (Blueprint $table): void {
            $table->timestamp('package_expires_at')->nullable()->index();
        });

        Schema::table('candidate_profiles', function (Blueprint $table): void {
            $table->boolean('share_contact_details')->default(false);
            $table->boolean('share_resume_with_employers')->default(false);
        });

        Schema::table('platform_settings', function (Blueprint $table): void {
            $table->boolean('package_purchases_enabled')->default(false);
            $table->boolean('paid_job_posting_required')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profile_accesses');
        Schema::dropIfExists('job_posting_uses');
        Schema::dropIfExists('payment_webhook_events');

        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropIndex(['package_expires_at']);
            $table->dropColumn('package_expires_at');
        });

        Schema::table('candidate_profiles', function (Blueprint $table): void {
            $table->dropColumn(['share_contact_details', 'share_resume_with_employers']);
        });

        Schema::table('platform_settings', function (Blueprint $table): void {
            $table->dropColumn(['package_purchases_enabled', 'paid_job_posting_required']);
        });

        Schema::dropIfExists('package_entitlements');
        Schema::dropIfExists('package_payments');
        Schema::dropIfExists('package_purchases');
        Schema::dropIfExists('packages');
    }
};
