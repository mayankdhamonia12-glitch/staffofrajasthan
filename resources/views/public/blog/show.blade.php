@extends('layouts.public')

@section('title', ($article->seo_title ?: $article->title).' | Staff of Rajasthan')
@section('description', $description)
@section('canonical', $canonical)
@section('og_type', 'article')
@section('robots', 'index,follow')
@if($article->featuredImageUrl())@section('og_image', $article->featuredImageUrl())@endif
@section('structured_data')
    <script type="application/ld+json">@json($structuredData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
    <script type="application/ld+json">@json($breadcrumbStructuredData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endsection

@section('content')
    <article class="bg-white">
        <header class="border-b border-slate-100 bg-[#f3f7ff]">
            <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
                <x-ui.breadcrumbs :items="[['label' => 'Blog', 'url' => route('blog.index')], ['label' => $article->title]]" />
                @if($preview ?? false)<p class="mt-8 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-900">Private preview · not publicly indexed</p>@endif
                <div class="mt-8 flex flex-wrap gap-2">@foreach($article->categories as $category)<a href="{{ route('blog.categories.show', $category) }}" class="rounded-full bg-white px-3 py-1.5 text-xs font-bold text-brand-700">{{ $category->name }}</a>@endforeach</div>
                <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-5xl">{{ $article->title }}</h1>
                @if($article->excerpt)<p class="mt-5 text-lg leading-8 text-slate-600">{{ $article->excerpt }}</p>@endif
                <div class="mt-7 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                    @if($article->author)
                        <a href="{{ route('blog.authors.show', $article->author->blogAuthorSlug()) }}" class="inline-flex items-center gap-3 font-semibold text-slate-800 hover:text-brand-700">
                            @if($article->author->blogProfileImageUrl())<img src="{{ $article->author->blogProfileImageUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">@else<span class="grid h-10 w-10 place-items-center rounded-full bg-brand-100 text-sm font-extrabold text-brand-800">{{ $article->author->initials() }}</span>@endif
                            <span>{{ $article->author->blogAuthorName() }}</span>
                        </a>
                        <span aria-hidden="true">·</span>
                    @endif
                    <time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->format('F j, Y') }}</time>
                    <span aria-hidden="true">·</span><span>{{ $article->readingTimeMinutes() }} min read</span>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-7xl px-4 py-9 sm:px-6 sm:py-12 lg:px-8">
            @if($article->featuredImageUrl())<figure class="mx-auto mb-10 max-w-5xl overflow-hidden rounded-3xl bg-slate-100"><img class="max-h-[34rem] w-full object-cover" src="{{ $article->featuredImageUrl() }}" alt="{{ $article->featured_image_alt ?: $article->title }}"></figure>@endif

            <div class="mx-auto grid max-w-6xl items-start gap-12 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <div>
                    <div class="article-prose max-w-none text-[1.05rem] leading-8 text-slate-700">{!! $article->renderedBody() !!}</div>
                    @if($article->tags->isNotEmpty())<div class="mt-10 flex flex-wrap items-center gap-2 border-t border-slate-200 pt-6"><span class="mr-1 text-sm font-bold text-slate-700">Topics:</span>@foreach($article->tags as $tag)<a href="{{ route('blog.tags.show', $tag) }}" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-brand-300 hover:text-brand-700">#{{ $tag->name }}</a>@endforeach</div>@endif
                    <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-6"><span class="text-sm font-bold text-slate-700">Share:</span><a target="_blank" rel="noopener noreferrer" href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($canonical) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">LinkedIn</a><a target="_blank" rel="noopener noreferrer" href="https://twitter.com/intent/tweet?url={{ urlencode($canonical) }}&text={{ urlencode($article->title) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">X</a></div>
                </div>

                <aside class="space-y-6 lg:sticky lg:top-28">
                    @if($article->author)<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-[.14em] text-brand-700">About the author</p><div class="mt-4 flex items-center gap-3">@if($article->author->blogProfileImageUrl())<img src="{{ $article->author->blogProfileImageUrl() }}" alt="" class="h-12 w-12 rounded-full object-cover">@else<span class="grid h-12 w-12 place-items-center rounded-full bg-brand-100 font-extrabold text-brand-800">{{ $article->author->initials() }}</span>@endif<div><a class="font-bold text-slate-900 hover:text-brand-700" href="{{ route('blog.authors.show', $article->author->blogAuthorSlug()) }}">{{ $article->author->blogAuthorName() }}</a><p class="text-xs text-slate-500">Staff of Rajasthan contributor</p></div></div>@if($article->author->blog_bio)<p class="mt-4 text-sm leading-6 text-slate-600">{{ $article->author->blog_bio }}</p>@endif</section>@endif
                    @if($recentArticles->isNotEmpty())<section class="rounded-2xl border border-slate-200 bg-white p-5"><h2 class="font-extrabold text-slate-950">Recent articles</h2><ul class="mt-4 space-y-4">@foreach($recentArticles as $recent)<li><a href="{{ route('blog.show', $recent) }}" class="text-sm font-bold leading-5 text-slate-800 hover:text-brand-700">{{ $recent->title }}</a><p class="mt-1 text-xs text-slate-500">{{ $recent->published_at?->format('M j, Y') }}</p></li>@endforeach</ul></section>@endif
                </aside>
            </div>
        </div>
    </article>

    @if($relatedArticles->isNotEmpty())
        <section class="border-t border-slate-100 bg-slate-50"><div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8"><div class="flex items-end justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-brand-700">Keep exploring</p><h2 class="mt-2 text-2xl font-extrabold text-slate-950">Related articles</h2></div><a href="{{ route('blog.index') }}" class="text-sm font-bold text-brand-700 hover:text-brand-900">All articles →</a></div><div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach($relatedArticles as $related)<x-blog-card :article="$related" />@endforeach</div></div></section>
    @endif
@endsection
