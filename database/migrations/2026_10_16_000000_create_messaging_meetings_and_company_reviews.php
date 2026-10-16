<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employer_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
            $table->index(['candidate_user_id', 'last_message_at']);
            $table->index(['employer_user_id', 'last_message_at']);
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
            $table->index(['conversation_id', 'sender_user_id', 'read_at']);
        });

        Schema::create('meetings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->timestamp('starts_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('timezone', 64)->default('Asia/Kolkata');
            $table->string('type', 20);
            $table->string('meeting_url', 2048)->nullable();
            $table->string('location', 255)->nullable();
            $table->text('agenda')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->timestamps();
            $table->index(['candidate_user_id', 'starts_at', 'status']);
            $table->index(['employer_user_id', 'starts_at', 'status']);
        });

        Schema::create('meeting_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event', 20);
            $table->timestamp('previous_starts_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->string('previous_status', 20)->nullable();
            $table->string('status', 20);
            $table->timestamps();
            $table->index(['meeting_id', 'created_at']);
        });

        Schema::create('meeting_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meeting_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['meeting_event_id', 'user_id'], 'meeting_event_user_delivery_unique');
        });

        Schema::create('company_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 160);
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->foreignId('moderated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'user_id'], 'company_reviews_company_candidate_unique');
            $table->index(['company_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_reviews');
        Schema::dropIfExists('meeting_notification_deliveries');
        Schema::dropIfExists('meeting_events');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
