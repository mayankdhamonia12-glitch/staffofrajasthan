<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogTaxonomyController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Article::class);
        $categories = BlogCategory::query()->withCount('articles')->orderBy('name')->get();
        $tags = BlogTag::query()->withCount('articles')->orderBy('name')->get();

        return view('admin.blog.taxonomies', compact('categories', 'tags'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $this->validated($request, 'blog_categories', 120, 140);
        $data['slug'] = $this->uniqueCategorySlug($data['slug'] ?: $data['name']);
        BlogCategory::query()->create($data);

        return back()->with('status', 'Category created.');
    }

    public function updateCategory(Request $request, BlogCategory $category): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $this->validated($request, 'blog_categories', 120, 140, $category->getKey());
        $data['slug'] = $this->uniqueCategorySlug($data['slug'] ?: $data['name'], $category);
        $category->update($data);

        return back()->with('status', 'Category updated.');
    }

    public function destroyCategory(BlogCategory $category): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $category->delete();

        return back()->with('status', 'Category deleted. Articles were kept; only this category association was removed.');
    }

    public function storeTag(Request $request): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $this->validated($request, 'blog_tags', 100, 120);
        $data['slug'] = $this->uniqueTagSlug($data['slug'] ?: $data['name']);
        BlogTag::query()->create($data);

        return back()->with('status', 'Tag created.');
    }

    public function updateTag(Request $request, BlogTag $tag): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $this->validated($request, 'blog_tags', 100, 120, $tag->getKey());
        $data['slug'] = $this->uniqueTagSlug($data['slug'] ?: $data['name'], $tag);
        $tag->update($data);

        return back()->with('status', 'Tag updated.');
    }

    public function destroyTag(BlogTag $tag): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $tag->delete();

        return back()->with('status', 'Tag deleted. Articles were kept; only this tag association was removed.');
    }

    /** @return array{name: string, slug: string|null, description: string|null} */
    private function validated(Request $request, string $table, int $nameMax, int $slugMax, int|string|null $ignoreId = null): array
    {
        $slugRule = Rule::unique($table, 'slug');

        if ($ignoreId !== null) {
            $slugRule->ignore($ignoreId);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:'.$nameMax],
            'slug' => ['nullable', 'string', 'max:'.$slugMax, 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        return [
            'name' => (string) $data['name'],
            'slug' => isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null,
            'description' => isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
        ];
    }

    private function uniqueCategorySlug(string $input, ?BlogCategory $except = null): string
    {
        return $this->uniqueSlug($input, $except?->getKey(), BlogCategory::class, 140);
    }

    private function uniqueTagSlug(string $input, ?BlogTag $except = null): string
    {
        return $this->uniqueSlug($input, $except?->getKey(), BlogTag::class, 120);
    }

    /** @param class-string<BlogCategory|BlogTag> $modelClass */
    private function uniqueSlug(string $input, int|string|null $ignoreId, string $modelClass, int $maxLength): string
    {
        $base = Str::limit(Str::slug($input) ?: 'topic', $maxLength - 10, '');
        $candidate = $base;
        $suffix = 2;

        while (true) {
            $query = $modelClass::query()->where('slug', $candidate);

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if (! $query->exists()) {
                return $candidate;
            }

            $candidate = $base.'-'.$suffix;
            $suffix++;
        }
    }
}
