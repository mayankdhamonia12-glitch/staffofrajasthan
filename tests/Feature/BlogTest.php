<?php

use App\ArticleStatus;
use App\Models\Article;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(fn () => $this->withoutVite());

function blogTestPublishedArticle(string $title, array $overrides = [], ?User $author = null): Article
{
    $author ??= User::factory()->admin()->create();

    if (! $author->blog_author_slug) {
        $author->forceFill(['blog_author_slug' => Str::slug($author->name).'-'.$author->getKey()])->save();
    }

    return Article::factory()->for($author, 'author')->create(array_merge([
        'title' => $title,
        'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
        'excerpt' => 'A real editorial summary for '.$title.'.',
        'body' => "## Useful guidance\n\nThis is published editorial content about careers and hiring.",
        'status' => ArticleStatus::Published,
        'published_at' => now()->subHour(),
    ], $overrides));
}

function blogTestPngUpload(): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/f5sAAAAASUVORK5CYII=');

    return UploadedFile::fake()->createWithContent('fixture.png', is_string($png) ? $png : '');
}

test('public blog directory shows only eligible published articles and supports real category and tag filters', function () {
    $featured = blogTestPublishedArticle('Featured career guide', ['is_featured' => true]);
    $category = BlogCategory::query()->create(['name' => 'Career advice', 'slug' => 'career-advice', 'description' => 'Practical career guidance.']);
    $tag = BlogTag::query()->create(['name' => 'Interview prep', 'slug' => 'interview-prep']);
    $matched = blogTestPublishedArticle('Preparing for interviews', ['title' => 'Preparing for interviews']);
    $matched->categories()->attach($category);
    $matched->tags()->attach($tag);
    $other = blogTestPublishedArticle('A separate hiring story');
    $draft = Article::factory()->create(['title' => 'Unpublished private draft', 'slug' => 'private-blog-draft']);
    $future = blogTestPublishedArticle('Scheduled story', ['slug' => 'scheduled-blog-story', 'published_at' => now()->addDay()]);

    $this->get(route('blog.index'))->assertOk()
        ->assertSee($featured->title)
        ->assertSee($matched->title)
        ->assertSee($other->title)
        ->assertDontSee($draft->title)
        ->assertDontSee($future->title);

    $this->get(route('blog.index', ['category' => $category->slug]))
        ->assertOk()->assertSee($matched->title)->assertDontSee($other->title);
    $this->get(route('blog.index', ['tag' => $tag->slug]))
        ->assertOk()->assertSee($matched->title)->assertDontSee($other->title);
});

test('homepage blog section uses latest published database articles and handles an empty search state', function () {
    $latest = blogTestPublishedArticle('Latest real career article');
    $draft = Article::factory()->create(['title' => 'Homepage private draft', 'slug' => 'homepage-private-draft']);

    $this->get(route('home'))->assertOk()
        ->assertSee($latest->title)
        ->assertSee(route('blog.show', $latest))
        ->assertDontSee($draft->title);
    $this->get(route('blog.index', ['keyword' => 'not-a-real-article-term']))
        ->assertOk()->assertSee('No articles found')->assertSee('Try a different keyword');
});

test('public article detail includes real SEO, safe Markdown, reading time, related and recent eligible content', function () {
    $category = BlogCategory::query()->create(['name' => 'Career advice', 'slug' => 'career-advice']);
    $tag = BlogTag::query()->create(['name' => 'Skills', 'slug' => 'skills']);
    $article = blogTestPublishedArticle('A readable career guide', [
        'slug' => 'readable-career-guide',
        'excerpt' => 'A useful summary for search and sharing.',
        'seo_title' => 'Career Guide SEO Title',
        'seo_description' => 'A specific search description for the article.',
        'body' => "## Safe section\n\nA short article body.\n\n<script>alert('xss')</script>\n\n[Unsafe link](javascript:alert(1))",
    ]);
    $article->categories()->attach($category);
    $article->tags()->attach($tag);
    $related = blogTestPublishedArticle('A related career article');
    $related->categories()->attach($category);
    $recent = blogTestPublishedArticle('A recent unrelated article');
    $draft = Article::factory()->create(['title' => 'Private related draft', 'slug' => 'private-related-draft']);
    $draft->categories()->attach($category);

    $response = $this->get(route('blog.show', $article));

    $response->assertOk()
        ->assertSee('Career Guide SEO Title')
        ->assertSee('A specific search description for the article.')
        ->assertSee('name="robots" content="index,follow"', false)
        ->assertSee('rel="canonical" href="'.route('blog.show', $article).'"', false)
        ->assertSee('property="og:type" content="article"', false)
        ->assertSee('"@type":"Article"', false)
        ->assertSee('<h2>Safe section</h2>', false)
        ->assertSee('1 min read')
        ->assertSee($related->title)
        ->assertSee($recent->title)
        ->assertDontSee('<script>alert', false)
        ->assertDontSee('href="javascript:', false)
        ->assertDontSee($draft->title);
});

test('draft, future-dated, and unknown article URLs are not public', function () {
    $draft = Article::factory()->create(['slug' => 'private-draft']);
    $future = blogTestPublishedArticle('Future article', ['slug' => 'future-article', 'published_at' => now()->addMinutes(5)]);

    $this->get(route('blog.show', $draft))->assertNotFound();
    $this->get(route('blog.show', $future))->assertNotFound();
    $this->get('/blog/unknown-article-slug')->assertNotFound();
});

test('blog search covers article text, supports sorting and list view, and paginates while preserving filters', function () {
    for ($number = 1; $number <= 13; $number++) {
        blogTestPublishedArticle('Searchable story '.str_pad((string) $number, 2, '0', STR_PAD_LEFT), [
            'excerpt' => 'A normal search result summary.',
            'body' => 'This also includes careerkeyword in the article body.',
            'published_at' => now()->subDays(14 - $number),
        ]);
    }
    blogTestPublishedArticle('Excerpt only result', [
        'excerpt' => 'excerpttoken is present in the summary only.',
        'body' => 'The article body does not contain that summary token.',
    ]);

    $firstPage = $this->get(route('blog.index', ['keyword' => 'careerkeyword', 'sort' => 'oldest', 'view' => 'list']))
        ->assertOk()->assertSee('Searchable story 01')->assertDontSee('Searchable story 13')->assertSee('page=2')->assertSee('sm:grid-cols-[16rem_minmax(0,1fr)]', false);
    $this->get(route('blog.index', ['keyword' => 'careerkeyword', 'sort' => 'oldest', 'view' => 'list', 'page' => 2]))
        ->assertOk()->assertSee('Searchable story 13')->assertDontSee('Searchable story 01');
    $this->get(route('blog.index', ['keyword' => 'excerpttoken']))->assertOk()->assertSee('Excerpt only result');

});

test('category and tag archives have canonical URLs and unknown archive slugs return 404', function () {
    $category = BlogCategory::query()->create(['name' => 'Interview guides', 'slug' => 'interview-guides', 'description' => 'Interview resources.']);
    $tag = BlogTag::query()->create(['name' => 'Resume', 'slug' => 'resume']);
    $article = blogTestPublishedArticle('Resume and interview article');
    $article->categories()->attach($category);
    $article->tags()->attach($tag);
    $other = blogTestPublishedArticle('Different topic article');

    $this->get(route('blog.categories.show', $category).'?sort=oldest&view=list')
        ->assertOk()->assertSee($article->title)->assertDontSee($other->title)
        ->assertSee('rel="canonical" href="'.route('blog.categories.show', $category).'"', false);
    $this->get(route('blog.tags.show', $tag))->assertOk()->assertSee($article->title)->assertDontSee($other->title);
    $this->get('/blog/category/not-a-category')->assertNotFound();
    $this->get('/blog/tag/not-a-tag')->assertNotFound();
});

test('public author pages show only published articles and never include private account details', function () {
    $author = User::factory()->admin()->create([
        'blog_author_slug' => 'career-editor-1234',
        'blog_display_name' => 'Career Editor',
        'blog_bio' => 'A short public biography for readers.',
    ]);
    $published = blogTestPublishedArticle('Author public article', [], $author);
    $draft = Article::factory()->for($author, 'author')->create(['title' => 'Author private draft', 'slug' => 'author-private-draft']);
    $otherAuthorArticle = blogTestPublishedArticle('Someone elses article');

    $this->get(route('blog.authors.show', $author->blog_author_slug))
        ->assertOk()->assertSee('Career Editor')->assertSee('A short public biography for readers.')
        ->assertSee($published->title)->assertDontSee($draft->title)->assertDontSee($otherAuthorArticle->title)
        ->assertDontSee($author->email);
});

test('admin and owner can manage real articles while candidates and employers cannot preview drafts', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->owner()->create();
    $otherAdmin = User::factory()->admin()->create();
    $candidate = User::factory()->candidate()->create();
    $employer = User::factory()->employer()->create();
    $article = Article::factory()->for($admin, 'author')->create(['title' => 'Restricted draft', 'slug' => 'restricted-draft']);
    $otherArticle = Article::factory()->for($otherAdmin, 'author')->create(['title' => 'Other admin draft', 'slug' => 'other-admin-draft']);

    $this->actingAs($candidate)->get(route('admin.blog.articles.index'))->assertForbidden();
    $this->actingAs($employer)->get(route('admin.blog.articles.preview', $article))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.blog.articles.preview', $article))->assertOk()->assertSee('not indexed or available')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get(route('admin.blog.articles.preview', $otherArticle))->assertForbidden();
    $this->get(route('admin.blog.articles.index'))->assertOk()->assertSee($article->title)->assertDontSee($otherArticle->title);
    $this->actingAs($owner)->get(route('admin.blog.articles.index'))->assertOk()->assertSee($article->title)->assertSee($otherArticle->title);
    $this->get(route('blog.show', $article))->assertNotFound();
});

test('authorized article editor creates, schedules, categorizes and uploads safe images', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    $scheduledAt = now()->addDays(2)->format('Y-m-d H:i:s');

    $this->actingAs($admin)->post(route('admin.blog.articles.store'), [
        'title' => 'A scheduled guide to skills',
        'excerpt' => 'A real article summary.',
        'body' => "## Skills that matter\n\nA useful, safe article body.",
        'status' => 'published',
        'published_at' => $scheduledAt,
        'is_featured' => '1',
        'category_names' => 'Career advice, Skills',
        'tag_names' => 'resumes, interviews',
        'featured_image_alt' => 'A candidate preparing for an interview',
        'seo_title' => 'Skills guide',
        'seo_description' => 'A scheduled editorial guide.',
        'featured_image' => blogTestPngUpload(),
    ])->assertRedirect();

    $article = Article::query()->where('slug', 'a-scheduled-guide-to-skills')->firstOrFail();
    expect($article->status)->toBe(ArticleStatus::Published)
        ->and($article->published_at?->toDateTimeString())->toBe($scheduledAt)
        ->and($article->author_id)->toBe($admin->id)
        ->and($article->is_featured)->toBeTrue()
        ->and($article->categories()->count())->toBe(2)
        ->and($article->tags()->count())->toBe(2)
        ->and($article->featured_image_path)->toStartWith('blog/articles/');
    Storage::disk('public')->assertExists($article->featured_image_path);
    $this->get(route('blog.show', $article))->assertNotFound();
    $this->get(route('admin.blog.articles.preview', $article))->assertOk()->assertSee($article->title);
});

test('article editor rejects executable uploads and unpublishing removes public visibility', function () {
    Storage::fake('public');
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->post(route('admin.blog.articles.store'), [
        'title' => 'Rejected executable upload',
        'body' => 'Safe body.',
        'status' => 'draft',
        'featured_image' => UploadedFile::fake()->create('malware.php', 12, 'application/x-php'),
    ])->assertSessionHasErrors('featured_image');
    expect(Article::query()->count())->toBe(0);

    $article = blogTestPublishedArticle('Article to unpublish', ['slug' => 'article-to-unpublish']);
    $this->actingAs($owner)->put(route('admin.blog.articles.update', $article), [
        'title' => $article->title,
        'slug' => $article->slug,
        'body' => $article->body,
        'status' => 'draft',
        'published_at' => $article->published_at?->format('Y-m-d H:i:s'),
    ])->assertRedirect();

    expect($article->fresh()->status)->toBe(ArticleStatus::Draft);
    $this->get(route('blog.show', $article))->assertNotFound();
    $this->get(route('blog.index'))->assertDontSee($article->title);
});

test('author profile editor validates images and only exposes intended author information', function () {
    Storage::fake('public');
    $owner = User::factory()->owner()->create(['email' => 'private-owner@example.test']);
    $this->actingAs($owner)->put(route('admin.blog.author-profile.update'), [
        'blog_display_name' => 'Rajasthan Career Editor',
        'blog_bio' => 'Public biography with professional context.',
        'profile_image' => blogTestPngUpload(),
    ])->assertRedirect();

    $owner->refresh();
    expect($owner->blog_display_name)->toBe('Rajasthan Career Editor')
        ->and($owner->blog_author_slug)->not->toBeNull()
        ->and($owner->blog_profile_image_path)->toStartWith('blog/authors/');
    Storage::disk('public')->assertExists($owner->blog_profile_image_path);

    blogTestPublishedArticle('Author profile visible only with posts', [], $owner);
    $this->get(route('blog.authors.show', $owner->blog_author_slug))
        ->assertOk()->assertSee('Rajasthan Career Editor')->assertSee('Public biography with professional context.')
        ->assertDontSee($owner->email);
});

test('admin and owner can manage taxonomy while other roles cannot', function () {
    $admin = User::factory()->admin()->create();
    $candidate = User::factory()->candidate()->create();
    $this->actingAs($candidate)->get(route('admin.blog.taxonomies.index'))->assertForbidden();

    $this->actingAs($admin)->post(route('admin.blog.categories.store'), [
        'name' => 'Government jobs',
        'description' => 'Public sector career opportunities.',
    ])->assertRedirect();
    $category = BlogCategory::query()->where('slug', 'government-jobs')->firstOrFail();
    $article = blogTestPublishedArticle('Government exam preparation');
    $article->categories()->attach($category);

    $this->put(route('admin.blog.categories.update', $category), [
        'name' => 'Government careers',
        'slug' => 'government-careers',
        'description' => 'Updated archive summary.',
    ])->assertRedirect();
    $category->refresh();
    expect($category->name)->toBe('Government careers')->and($category->description)->toBe('Updated archive summary.');

    $this->post(route('admin.blog.tags.store'), ['name' => 'Career news', 'description' => 'News and updates.'])->assertRedirect();
    $tag = BlogTag::query()->where('slug', 'career-news')->firstOrFail();
    $article->tags()->attach($tag);
    $this->delete(route('admin.blog.categories.destroy', $category))->assertRedirect();
    expect(Article::query()->whereKey($article->getKey())->exists())->toBeTrue()
        ->and($article->fresh()->categories()->count())->toBe(0);
    $this->actingAs(User::factory()->owner()->create())->get(route('admin.blog.taxonomies.index'))->assertOk()->assertSee('Career news');
});
