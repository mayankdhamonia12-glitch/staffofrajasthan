<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('blog_author_slug')->nullable();
            $table->unique('blog_author_slug');
            $table->string('blog_display_name')->nullable();
            $table->text('blog_bio')->nullable();
            $table->string('blog_profile_image_path')->nullable();
        });

        Schema::create('blog_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('featured_image_path')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
            $table->index(['is_featured', 'status', 'published_at']);
        });

        Schema::create('article_blog_category', function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_category_id')->constrained('blog_categories')->cascadeOnDelete();
            $table->primary(['article_id', 'blog_category_id']);
        });

        Schema::create('article_blog_tag', function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->primary(['article_id', 'blog_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_blog_tag');
        Schema::dropIfExists('article_blog_category');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['blog_author_slug']);
            $table->dropColumn([
                'blog_author_slug',
                'blog_display_name',
                'blog_bio',
                'blog_profile_image_path',
            ]);
        });
    }
};
