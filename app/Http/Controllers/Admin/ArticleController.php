<?php

namespace App\Http\Controllers\Admin;

use App\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\ArticlePublishingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Article::class);

        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_column(ArticleStatus::cases(), 'value'))],
        ]);

        $query = Article::query()
            ->with('author')
            ->when(($filters['keyword'] ?? '') !== '', function ($query) use ($filters): void {
                $term = '%'.trim((string) $filters['keyword']).'%';
                $query->where(fn ($search) => $search->where('title', 'like', $term)->orWhere('slug', 'like', $term));
            })
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('status', $filters['status']));

        $viewer = $this->user($request);

        if ($viewer->isAdmin() && ! $viewer->isOwner()) {
            $query->where('author_id', $viewer->getKey());
        }

        $articles = $query
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.blog.articles.index', compact('articles', 'filters'));
    }

    public function create(): View
    {
        $this->authorize('create', Article::class);

        return view('admin.blog.articles.form', ['article' => null]);
    }

    public function store(Request $request, ArticlePublishingService $publishing, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $this->validatedArticleData($request);
        $article = $publishing->save($this->user($request), $data, null, $this->uploadedFile($request, 'featured_image'));
        $audit->record($this->user($request), 'blog.article_created', $article, 'Created blog article '.$article->getKey().'.', null, null, ['title' => $article->title, 'status' => $article->status->value, 'is_featured' => $article->is_featured]);

        return redirect()->route('admin.blog.articles.edit', $article)->with('status', 'Article saved.');
    }

    public function edit(Article $article): View
    {
        $this->authorize('update', $article);
        $article->load(['categories', 'tags', 'author']);

        return view('admin.blog.articles.form', compact('article'));
    }

    public function update(Request $request, Article $article, ArticlePublishingService $publishing, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $article);
        $before = ['title' => $article->title, 'status' => $article->status->value, 'is_featured' => $article->is_featured];
        $data = $this->validatedArticleData($request, $article);
        $article = $publishing->save($this->user($request), $data, $article, $this->uploadedFile($request, 'featured_image'));
        $audit->record($this->user($request), 'blog.article_updated', $article, 'Updated blog article '.$article->getKey().'.', null, $before, ['title' => $article->title, 'status' => $article->status->value, 'is_featured' => $article->is_featured]);

        return redirect()->route('admin.blog.articles.edit', $article)->with('status', 'Article saved.');
    }

    public function preview(Article $article): Response
    {
        $this->authorize('view', $article);
        $article->load(['author', 'categories', 'tags']);

        return response()->view('admin.blog.articles.preview', compact('article'))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function destroy(Request $request, Article $article, ArticlePublishingService $publishing, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $article);
        $before = ['title' => $article->title, 'status' => $article->status->value];
        $publishing->delete($article);
        $audit->record($this->user($request), 'blog.article_deleted', $article, 'Deleted blog article '.$article->getKey().'.', null, $before, null);

        return redirect()->route('admin.blog.articles.index')->with('status', 'Article deleted.');
    }

    public function authorProfile(Request $request): View
    {
        $this->authorize('create', Article::class);
        $author = $this->user($request);

        return view('admin.blog.author-profile', compact('author'));
    }

    public function updateAuthorProfile(Request $request, ArticlePublishingService $publishing, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $request->validate([
            'blog_display_name' => ['nullable', 'string', 'max:120'],
            'blog_bio' => ['nullable', 'string', 'max:2000'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
            'remove_profile_image' => ['nullable', 'boolean'],
        ]);

        $author = $this->user($request);
        $before = ['blog_display_name' => $author->blog_display_name, 'blog_author_slug' => $author->blog_author_slug];
        $author = $publishing->updateAuthorProfile($author, $data, $this->uploadedFile($request, 'profile_image'));
        $audit->record($author, 'blog.author_profile_updated', $author, 'Updated blog author profile.', null, $before, ['blog_display_name' => $author->blog_display_name, 'blog_author_slug' => $author->blog_author_slug]);

        return back()->with('status', 'Author profile updated.');
    }

    /** @return array<string, mixed> */
    private function validatedArticleData(Request $request, ?Article $article = null): array
    {
        $slugRule = Rule::unique('articles', 'slug');

        if ($article instanceof Article) {
            $slugRule->ignore($article->getKey());
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string', 'max:100000'],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'remove_featured_image' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'category_names' => ['nullable', 'string', 'max:1200'],
            'tag_names' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['categories'] = $this->splitNames($data['category_names'] ?? null);
        $data['tags'] = $this->splitNames($data['tag_names'] ?? null);

        return $data;
    }

    /** @return array<int, string> */
    private function splitNames(mixed $value): array
    {
        if (! is_string($value)) {
            return [];
        }

        $names = array_map(trim(...), explode(',', $value));

        return array_values(array_unique(array_filter($names, fn (string $name): bool => $name !== '')));
    }

    private function uploadedFile(Request $request, string $key): ?UploadedFile
    {
        $file = $request->file($key);

        return $file instanceof UploadedFile ? $file : null;
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isOwner()), 403);

        return $user;
    }
}
