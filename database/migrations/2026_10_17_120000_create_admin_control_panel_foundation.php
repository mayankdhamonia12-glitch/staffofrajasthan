<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_suspended')->default(false)->index();
            $table->timestamp('suspended_at')->nullable();
            $table->foreignId('suspended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('suspension_reason', 500)->nullable();
            $table->index(['role', 'is_suspended']);
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->foreignId('moderated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderation_reason', 500)->nullable();
        });

        Schema::table('company_reviews', function (Blueprint $table): void {
            $table->string('moderation_note', 500)->nullable();
        });

        Schema::table('skills', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('site_name', 120)->default('Staff of Rajasthan');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('locale', 12)->default('en');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('subject_type', 160)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('summary', 500);
            $table->string('reason', 500)->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id', 'created_at'], 'admin_audit_subject_created_index');
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('platform_settings');

        Schema::table('skills', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });

        Schema::table('company_reviews', function (Blueprint $table): void {
            $table->dropColumn('moderation_note');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('moderated_by_user_id');
            $table->dropColumn(['moderated_at', 'moderation_reason']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role', 'is_suspended']);
            $table->dropConstrainedForeignId('suspended_by_user_id');
            $table->dropColumn(['is_suspended', 'suspended_at', 'suspension_reason']);
        });
    }
};
