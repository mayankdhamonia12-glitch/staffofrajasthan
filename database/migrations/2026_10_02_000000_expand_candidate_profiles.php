<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->string('profile_photo_path')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('experience_level', 50)->nullable()->index();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->unsignedTinyInteger('months_experience')->nullable();
            $table->unsignedInteger('expected_salary')->nullable()->index();
            $table->string('salary_type', 20)->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('website_url')->nullable();
            $table->string('video_provider', 20)->nullable();
            $table->string('intro_video_url', 2048)->nullable();
            $table->string('intro_video_title', 160)->nullable();
            $table->string('resume_title', 160)->nullable();
            $table->string('resume_original_name')->nullable();
            $table->string('resume_mime_type', 150)->nullable();
            $table->unsignedInteger('resume_size')->nullable();
            $table->timestamp('resume_uploaded_at')->nullable();
            $table->string('availability_status', 30)->default('open_to_work')->index();
            $table->boolean('is_public')->default(false)->index();
            $table->boolean('show_expected_salary')->default(false);
            $table->json('preferred_employment_types')->nullable();
        });

        Schema::table('educations', function (Blueprint $table) {
            $table->boolean('currently_studying')->default(false)->after('end_date');
        });

        Schema::table('candidate_profile_skill', function (Blueprint $table) {
            $table->string('proficiency', 30)->default('intermediate');
        });

        Schema::create('candidate_profile_job_category', function (Blueprint $table) {
            $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['candidate_profile_id', 'job_category_id']);
        });

        Schema::create('candidate_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->string('language', 80);
            $table->string('proficiency', 20);
            $table->timestamps();
            $table->unique(['candidate_profile_id', 'language']);
        });

        Schema::create('candidate_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('url', 2048)->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('issuer', 160)->nullable();
            $table->date('awarded_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'candidate_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_shortlists');
        Schema::dropIfExists('candidate_awards');
        Schema::dropIfExists('candidate_portfolios');
        Schema::dropIfExists('candidate_languages');
        Schema::dropIfExists('candidate_profile_job_category');

        Schema::table('candidate_profile_skill', fn (Blueprint $table) => $table->dropColumn('proficiency'));
        Schema::table('educations', fn (Blueprint $table) => $table->dropColumn('currently_studying'));
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['experience_level']);
            $table->dropIndex(['expected_salary']);
            $table->dropIndex(['city']);
            $table->dropIndex(['availability_status']);
            $table->dropIndex(['is_public']);
            $table->dropColumn([
                'slug', 'profile_photo_path', 'date_of_birth', 'gender', 'experience_level',
                'years_experience', 'months_experience', 'expected_salary', 'salary_type',
                'city', 'state', 'country', 'address', 'latitude', 'longitude', 'facebook_url',
                'twitter_url', 'website_url', 'video_provider', 'intro_video_url', 'intro_video_title',
                'resume_title', 'resume_original_name', 'resume_mime_type', 'resume_size',
                'resume_uploaded_at', 'availability_status', 'is_public', 'show_expected_salary',
                'preferred_employment_types',
            ]);
        });
    }
};
