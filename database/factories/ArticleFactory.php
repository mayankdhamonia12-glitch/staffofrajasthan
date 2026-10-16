<?php

namespace Database\Factories;

use App\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'author_id' => User::factory()->admin(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'body' => "## Useful guidance\n\n".fake()->paragraph(),
            'featured_image_path' => null,
            'featured_image_alt' => null,
            'status' => ArticleStatus::Draft,
            'is_featured' => false,
            'published_at' => null,
            'seo_title' => null,
            'seo_description' => null,
        ];
    }
}
