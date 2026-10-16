<?php

namespace App\Services;

use App\ArticleStatus;
use App\Models\Article;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ArticlePublishingService
{
    /**
     * @param array<string, mixed> $data Validated editorial fields.
     */
    public function save(User $author, array $data, ?Article $article = null, ?UploadedFile $featuredImage = null): Article
    {
        $article ??= new Article;
        $oldImagePath = $article->featured_image_path;
        $newImagePath = null;

        if ($featuredImage instanceof UploadedFile) {
            $storedPath = $featuredImage->storePublicly('blog/articles', 'public');

            if (! is_string($storedPath) || $storedPath === '') {
                throw ValidationException::withMessages(['featured_image' => 'The featured image could not be stored. Please try again.']);
            }

            $newImagePath = $storedPath;
        }

        try {
            DB::transaction(function () use ($article, $author, $data, $newImagePath): void {
                $title = trim((string) $data['title']);
                $slugInput = trim((string) ($data['slug'] ?? ''));
                $status = ArticleStatus::from((string) $data['status']);
                $publishedAt = isset($data['published_at']) && $data['published_at'] !== ''
                    ? $data['published_at']
                    : null;

                if ($status === ArticleStatus::Published && $publishedAt === null) {
                    $publishedAt = now();
                }

                if (! $article->exists) {
                    $article->author()->associate($author);
                }

                $article->fill([
                    'title' => $title,
                    'slug' => $this->uniqueSlug($slugInput !== '' ? $slugInput : $title, $article->exists ? (int) $article->getKey() : null),
                    'excerpt' => $this->nullableString($data['excerpt'] ?? null),
                    'body' => trim((string) $data['body']),
                    'featured_image_path' => $newImagePath ?? ((bool) ($data['remove_featured_image'] ?? false) ? null : $article->featured_image_path),
                    'featured_image_alt' => $this->nullableString($data['featured_image_alt'] ?? null),
                    'status' => $status,
                    'is_featured' => (bool) ($data['is_featured'] ?? false),
                    'published_at' => $publishedAt,
                    'seo_title' => $this->nullableString($data['seo_title'] ?? null),
                    'seo_description' => $this->nullableString($data['seo_description'] ?? null),
                ]);
                $article->save();

                $this->syncCategories($article, $data['categories'] ?? []);
                $this->syncTags($article, $data['tags'] ?? []);
            });
        } catch (Throwable $exception) {
            if (is_string($newImagePath)) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if (($newImagePath !== null || (bool) ($data['remove_featured_image'] ?? false)) && $oldImagePath !== null && $oldImagePath !== $newImagePath) {
            $this->deleteImageIfUnreferenced($oldImagePath, $article);
        }

        $articleAuthor = $article->author()->first();

        if ($article->status === ArticleStatus::Published && $articleAuthor instanceof User && ! $articleAuthor->blog_author_slug) {
            $articleAuthor->forceFill([
                'blog_author_slug' => $this->uniqueAuthorSlug($articleAuthor),
            ])->save();
        }

        return $article->load(['author', 'categories', 'tags']);
    }

    /** @param array<string, mixed> $data */
    public function updateAuthorProfile(User $author, array $data, ?UploadedFile $profileImage = null): User
    {
        $oldImagePath = $author->blog_profile_image_path;
        $newImagePath = null;

        if ($profileImage instanceof UploadedFile) {
            $storedPath = $profileImage->storePublicly('blog/authors', 'public');

            if (! is_string($storedPath) || $storedPath === '') {
                throw ValidationException::withMessages(['profile_image' => 'The profile image could not be stored. Please try again.']);
            }

            $newImagePath = $storedPath;
        }

        try {
            $author->forceFill([
                'blog_display_name' => $this->nullableString($data['blog_display_name'] ?? null),
                'blog_bio' => $this->nullableString($data['blog_bio'] ?? null),
                'blog_profile_image_path' => $newImagePath ?? ((bool) ($data['remove_profile_image'] ?? false) ? null : $oldImagePath),
                'blog_author_slug' => $author->blog_author_slug ?: $this->uniqueAuthorSlug($author, $this->nullableString($data['blog_display_name'] ?? null)),
            ])->save();
        } catch (Throwable $exception) {
            if (is_string($newImagePath)) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if (($newImagePath !== null || (bool) ($data['remove_profile_image'] ?? false)) && $oldImagePath !== null && $oldImagePath !== $newImagePath) {
            $this->deleteImageIfUnreferenced($oldImagePath, null);
        }

        return $author->refresh();
    }

    public function delete(Article $article): void
    {
        $imagePath = $article->featured_image_path;
        $article->delete();

        if (is_string($imagePath) && $imagePath !== '') {
            $this->deleteImageIfUnreferenced($imagePath, null);
        }
    }

    private function uniqueSlug(string $input, ?int $ignoreArticleId): string
    {
        $base = Str::limit(Str::slug($input) ?: 'article', 220, '');
        $candidate = $base;
        $suffix = 2;

        while (true) {
            $query = Article::query()->where('slug', $candidate);

            if ($ignoreArticleId !== null) {
                $query->where('id', '!=', $ignoreArticleId);
            }

            if (! $query->exists()) {
                return $candidate;
            }

            $candidate = $base.'-'.$suffix;
            $suffix++;
        }
    }

    private function uniqueAuthorSlug(User $author, ?string $displayName = null): string
    {
        $base = Str::slug($displayName ?: (string) ($author->blog_display_name ?: $author->name)) ?: 'author';
        $candidate = $base.'-'.$author->getKey();

        return $candidate;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /** @param array<int, mixed> $names */
    private function syncCategories(Article $article, array $names): void
    {
        $ids = [];

        foreach ($names as $name) {
            if (! is_string($name)) {
                continue;
            }

            $label = trim($name);
            $slug = Str::slug($label);

            if ($label === '' || $slug === '') {
                continue;
            }

            $category = BlogCategory::query()->firstOrCreate(['slug' => $slug], ['name' => $label]);
            $ids[] = (int) $category->getKey();
        }

        $article->categories()->sync(array_values(array_unique($ids)));
    }

    /** @param array<int, mixed> $names */
    private function syncTags(Article $article, array $names): void
    {
        $ids = [];

        foreach ($names as $name) {
            if (! is_string($name)) {
                continue;
            }

            $label = trim($name);
            $slug = Str::slug($label);

            if ($label === '' || $slug === '') {
                continue;
            }

            $tag = BlogTag::query()->firstOrCreate(['slug' => $slug], ['name' => $label]);
            $ids[] = (int) $tag->getKey();
        }

        $article->tags()->sync(array_values(array_unique($ids)));
    }

    private function deleteImageIfUnreferenced(string $path, ?Article $except): void
    {
        $query = Article::query()->where('featured_image_path', $path);

        if ($except instanceof Article && $except->exists) {
            $query->where('id', '!=', $except->getKey());
        }

        $referencedByAuthor = User::query()->where('blog_profile_image_path', $path)->exists();

        if (! $query->exists() && ! $referencedByAuthor) {
            Storage::disk('public')->delete($path);
        }
    }
}
