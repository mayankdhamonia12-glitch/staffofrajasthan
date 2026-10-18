<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:140'],
            'tag' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', Rule::in(['latest', 'oldest'])],
            'view' => ['nullable', Rule::in(['grid', 'list'])],
        ]);
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $categorySlug = (string) ($filters['category'] ?? '');
        $tagSlug = (string) ($filters['tag'] ?? '');
        $sort = (string) ($filters['sort'] ?? 'latest');

        $featuredArticle = null;

        if ($keyword === '' && $categorySlug === '' && $tagSlug === '') {
            $featuredArticle = $this->publishedArticles()
                ->featured()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->first();
        }

        $articles = $this->publishedArticles()
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $term = '%'.$keyword.'%';
                $query->where(function (Builder $search) use ($term): void {
                    $search->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('body', 'like', $term);
                });
            })
            ->when($categorySlug !== '', fn (Builder $query) => $query->whereHas('categories', fn (Builder $categories) => $categories->where('slug', $categorySlug)))
            ->when($tagSlug !== '', fn (Builder $query) => $query->whereHas('tags', fn (Builder $tags) => $tags->where('slug', $tagSlug)))
            ->when($featuredArticle instanceof Article, fn (Builder $query) => $query->where('id', '!=', $featuredArticle->getKey()))
            ->when($sort === 'oldest', fn (Builder $query) => $query->orderBy('published_at')->orderBy('id'))
            ->when($sort !== 'oldest', fn (Builder $query) => $query->orderByDesc('published_at')->orderByDesc('id'))
            ->paginate(12)
            ->withQueryString();

        $categories = BlogCategory::query()
            ->whereHas('articles', fn (Builder $query) => $query->published())
            ->orderBy('name')
            ->get();
        $tags = BlogTag::query()
            ->whereHas('articles', fn (Builder $query) => $query->published())
            ->orderBy('name')
            ->get();

        return view('public.blog.index', [
            'articles' => $articles,
            'featuredArticle' => $featuredArticle,
            'categories' => $categories,
            'tags' => $tags,
            'filters' => [
                'keyword' => $keyword,
                'category' => $categorySlug,
                'tag' => $tagSlug,
                'sort' => $sort,
                'view' => (string) ($filters['view'] ?? 'grid'),
            ],
            'pageHeading' => $keyword !== '' ? 'Search results' : 'Ideas for your next move',
            'pageDescription' => $keyword !== ''
                ? 'Articles matching “'.$keyword.'” from Staff of Rajasthan.'
                : 'Insights for candidates, employers, and teams across Rajasthan.',
            'canonical' => route('blog.index'),
            'filterResetUrl' => route('blog.index'),
            'isArchive' => false,
        ]);
    }

    public function category(Request $request, BlogCategory $category): View
    {
        return $this->archive($request, 'categories', $category->slug, $category->name, $category->description ?: 'Career and employment articles filed under '.$category->name.'.', route('blog.categories.show', $category));
    }

    public function tag(Request $request, BlogTag $tag): View
    {
        return $this->archive($request, 'tags', $tag->slug, 'Articles tagged “'.$tag->name.'”', $tag->description ?: 'Career and employment articles tagged '.$tag->name.'.', route('blog.tags.show', $tag));
    }

    public function author(Request $request, User $author): View
    {
        $author = User::query()
            ->select(['id', 'name', 'blog_author_slug', 'blog_display_name', 'blog_bio', 'blog_profile_image_path'])
            ->whereKey($author->getKey())
            ->firstOrFail();

        $authorArticles = $author->blogArticles()->published()->exists();
        abort_unless($authorArticles, 404);

        $articles = $this->publishedArticles()
            ->where('author_id', $author->getKey())
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('public.blog.author', [
            'author' => $author,
            'articles' => $articles,
            'canonical' => route('blog.authors.show', $author->blogAuthorSlug()),
            'profileStructuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                'name' => $author->blogAuthorName(),
                'url' => route('blog.authors.show', $author->blogAuthorSlug()),
                'mainEntity' => array_filter([
                    '@type' => 'Person',
                    'name' => $author->blogAuthorName(),
                    'description' => $author->blog_bio,
                ], fn (mixed $value): bool => $value !== null),
            ],
        ]);
    }

    public function show(Article $article): View
    {
        abort_unless($article->status->value === 'published' && $article->published_at !== null && $article->published_at->lte(now()), 404);

        $article->load([
            'author:id,name,blog_author_slug,blog_display_name,blog_bio,blog_profile_image_path',
            'categories:id,name,slug,description',
            'tags:id,name,slug',
        ]);

        $relatedArticles = $this->publishedArticles()
            ->where('id', '!=', $article->getKey())
            ->where(function (Builder $query) use ($article): void {
                $categoryIds = $article->categories->modelKeys();
                $tagIds = $article->tags->modelKeys();

                if ($categoryIds === [] && $tagIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                if ($categoryIds !== [] && $tagIds !== []) {
                    $query->where(function (Builder $matches) use ($categoryIds, $tagIds): void {
                        $matches->whereHas('categories', fn (Builder $categories) => $categories->whereIn('blog_categories.id', $categoryIds))
                            ->orWhereHas('tags', fn (Builder $tags) => $tags->whereIn('blog_tags.id', $tagIds));
                    });

                    return;
                }

                if ($categoryIds !== []) {
                    $query->whereHas('categories', fn (Builder $categories) => $categories->whereIn('blog_categories.id', $categoryIds));
                } else {
                    $query->whereHas('tags', fn (Builder $tags) => $tags->whereIn('blog_tags.id', $tagIds));
                }
            })
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $recentArticles = $this->publishedArticles()
            ->where('id', '!=', $article->getKey())
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $canonical = route('blog.show', $article);
        $description = $article->seo_description ?: ($article->excerpt ?: $article->title);
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $description,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
            'publisher' => ['@type' => 'Organization', 'name' => 'Staff of Rajasthan', 'url' => url('/')],
        ];

        if ($article->author instanceof User) {
            $structuredData['author'] = ['@type' => 'Person', 'name' => $article->author->blogAuthorName()];
        }

        if (($imageUrl = $article->featuredImageUrl()) !== null) {
            $structuredData['image'] = [$imageUrl];
        }

        $breadcrumbStructuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $article->title, 'item' => $canonical],
            ],
        ];

        return view('public.blog.show', compact('article', 'relatedArticles', 'recentArticles', 'canonical', 'description', 'structuredData', 'breadcrumbStructuredData'));
    }

    /**
     * @param  'categories'|'tags'  $relationName
     */
    private function archive(Request $request, string $relationName, string $slug, string $heading, string $description, string $canonical): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', Rule::in(['latest', 'oldest'])],
            'view' => ['nullable', Rule::in(['grid', 'list'])],
        ]);
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $sort = (string) ($filters['sort'] ?? 'latest');
        $query = Article::query()
            ->published()
            ->whereHas($relationName, fn (Builder $relation) => $relation->where('slug', $slug))
            ->with(['author:id,name,blog_display_name,blog_author_slug,blog_profile_image_path', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $term = '%'.$keyword.'%';
                $query->where(fn (Builder $search) => $search->where('title', 'like', $term)->orWhere('excerpt', 'like', $term)->orWhere('body', 'like', $term));
            })
            ->when($sort === 'oldest', fn (Builder $query) => $query->orderBy('published_at')->orderBy('id'))
            ->when($sort !== 'oldest', fn (Builder $query) => $query->orderByDesc('published_at')->orderByDesc('id'));

        return view('public.blog.index', [
            'articles' => $query->paginate(12)->withQueryString(),
            'featuredArticle' => null,
            'categories' => BlogCategory::query()->whereHas('articles', fn (Builder $query) => $query->published())->orderBy('name')->get(),
            'tags' => BlogTag::query()->whereHas('articles', fn (Builder $query) => $query->published())->orderBy('name')->get(),
            'filters' => ['keyword' => $keyword, 'category' => '', 'tag' => '', 'sort' => $sort, 'view' => (string) ($filters['view'] ?? 'grid')],
            'pageHeading' => $heading,
            'pageDescription' => $description,
            'canonical' => $canonical,
            'filterResetUrl' => $canonical,
            'isArchive' => true,
        ]);
    }

    /** @return Builder<Article> */
    private function publishedArticles(): Builder
    {
        return Article::query()
            ->published()
            ->with([
                'author:id,name,blog_display_name,blog_author_slug,blog_profile_image_path',
                'categories:id,name,slug',
                'tags:id,name,slug',
            ]);
    }
}
