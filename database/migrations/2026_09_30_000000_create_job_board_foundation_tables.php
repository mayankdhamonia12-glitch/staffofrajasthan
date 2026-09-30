<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('jobs', 'queue_jobs');

        Schema::create('job_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('state')->default('Rajasthan');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['name', 'state']);
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->text('description')->nullable();
            $table->string('company_size')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
            $table->index(['user_id', 'is_visible']);
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->longText('responsibilities')->nullable();
            $table->longText('requirements')->nullable();
            $table->string('employment_type')->index();
            $table->string('experience_level')->nullable()->index();
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->string('salary_currency', 3)->default('INR');
            $table->string('salary_period')->nullable();
            $table->unsignedInteger('vacancies')->nullable();
            $table->date('application_deadline')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_urgent')->default(false)->index();
            $table->boolean('is_filled')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->index(['status', 'published_at']);
            $table->index(['location_id', 'employment_type']);
        });

        Schema::create('job_skill', function (Blueprint $table) {
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['job_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_skill');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('industries');
        Schema::dropIfExists('job_categories');
        Schema::rename('queue_jobs', 'jobs');
    }
};
