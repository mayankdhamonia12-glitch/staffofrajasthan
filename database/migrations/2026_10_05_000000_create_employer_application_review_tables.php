<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('job_applications')->where('status', 'submitted')->update(['status' => 'applied']);
        DB::table('job_applications')->where('status', 'reviewing')->update(['status' => 'shortlisted']);
        DB::table('job_applications')->where('status', 'accepted')->update(['status' => 'selected']);

        Schema::table('job_applications', function (Blueprint $table) {
            $table->index(['job_id', 'status', 'created_at'], 'job_applications_job_status_created_index');
        });

        Schema::create('job_application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['job_application_id', 'created_at'], 'job_application_status_history_application_created_index');
        });

        Schema::create('job_application_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['job_application_id', 'employer_user_id', 'created_at'], 'job_application_notes_application_employer_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_application_notes');
        Schema::dropIfExists('job_application_status_history');
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex('job_applications_job_status_created_index');
        });
    }
};
