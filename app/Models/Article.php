<?php

namespace App\Models;

use App\ArticleStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $author_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $body Markdown-formatted editorial content
 * @property string|null $featured_image_path
 * @property string|null $featured_image_alt
 * @property ArticleStatus $status
 * @property bool $is_featured
 * @property Carbon|null $published_at
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'author_id', 'title', 'slug', 'excerpt', 'body', 'featured_image_path', 'featured_image_alt',
    'status', 'is_featured', 'published_at', 'seo_title', 'seo_description',
])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsToMany<BlogCategory, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BlogCategory::class, 'article_blog_category')->orderBy('name');
    }

    /** @return BelongsToMany<BlogTag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'article_blog_tag')->orderBy('name');
    }

    /** @param Builder<Article> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** @param Builder<Article> $query */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function featuredImageUrl(): ?string
    {
        if (! $this->featured_image_path || ! Storage::disk('public')->exists($this->featured_image_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->featured_image_path);
    }

    /** Return rendered HTML from Markdown with raw HTML and unsafe links disabled. */
    public function renderedBody(): string
    {
        return Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function readingTimeMinutes(): int
    {
        $wordCount = preg_match_all('/[\p{L}\p{N}]+/u', $this->body);

        return max(1, (int) ceil(($wordCount === false ? 0 : $wordCount) / 200));
    }
}
