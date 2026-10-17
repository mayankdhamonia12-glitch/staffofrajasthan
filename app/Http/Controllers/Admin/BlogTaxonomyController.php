<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Services\AdminAuditLogger;
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

    public function storeCategory(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $this->validated($request, 'blog_categories', 120, 140);
        $data['slug'] = $this->uniqueCategorySlug($data['slug'] ?: $data['name']);
        $category = BlogCategory::query()->create($data);
        $audit->record($request->user(), 'blog.category_created', $category, 'Created blog category '.$category->getKey().'.', null, null, ['name' => $category->name, 'slug' => $category->slug]);

        return back()->with('status', 'Category created.');
    }

    public function updateCategory(Request $request, BlogCategory $category, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $this->validated($request, 'blog_categories', 120, 140, $category->getKey());
        $data['slug'] = $this->uniqueCategorySlug($data['slug'] ?: $data['name'], $category);
        $before = ['name' => $category->name, 'slug' => $category->slug];
        $category->update($data);
        $audit->record($request->user(), 'blog.category_updated', $category, 'Updated blog category '.$category->getKey().'.', null, $before, ['name' => $category->name, 'slug' => $category->slug]);

        return back()->with('status', 'Category updated.');
    }

    public function destroyCategory(Request $request, BlogCategory $category, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $before = ['name' => $category->name, 'slug' => $category->slug];
        $category->delete();
        $audit->record($request->user(), 'blog.category_deleted', $category, 'Deleted blog category '.$category->getKey().'.', null, $before, null);

        return back()->with('status', 'Category deleted. Articles were kept; only this category association was removed.');
    }

    public function storeTag(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $this->validated($request, 'blog_tags', 100, 120);
        $data['slug'] = $this->uniqueTagSlug($data['slug'] ?: $data['name']);
        $tag = BlogTag::query()->create($data);
        $audit->record($request->user(), 'blog.tag_created', $tag, 'Created blog tag '.$tag->getKey().'.', null, null, ['name' => $tag->name, 'slug' => $tag->slug]);

        return back()->with('status', 'Tag created.');
    }

    public function updateTag(Request $request, BlogTag $tag, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $this->validated($request, 'blog_tags', 100, 120, $tag->getKey());
        $data['slug'] = $this->uniqueTagSlug($data['slug'] ?: $data['name'], $tag);
        $before = ['name' => $tag->name, 'slug' => $tag->slug];
        $tag->update($data);
        $audit->record($request->user(), 'blog.tag_updated', $tag, 'Updated blog tag '.$tag->getKey().'.', null, $before, ['name' => $tag->name, 'slug' => $tag->slug]);

        return back()->with('status', 'Tag updated.');
    }

    public function destroyTag(Request $request, BlogTag $tag, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $before = ['name' => $tag->name, 'slug' => $tag->slug];
        $tag->delete();
        $audit->record($request->user(), 'blog.tag_deleted', $tag, 'Deleted blog tag '.$tag->getKey().'.', null, $before, null);

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
