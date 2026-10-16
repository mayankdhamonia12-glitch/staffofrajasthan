@extends('layouts.public')

@section('title', $pageHeading.' | Career Blog | Staff of Rajasthan')
@section('description', $pageDescription)
@section('canonical', $canonical)
@section('robots', $filters['keyword'] !== '' || $filters['category'] !== '' || $filters['tag'] !== '' ? 'noindex,follow' : 'index,follow')
@php($collectionStructuredData = ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => $pageHeading, 'description' => $pageDescription, 'url' => $canonical])
@section('structured_data')
    <script type="application/ld+json">@json($collectionStructuredData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endsection

@section('content')
    <section class="bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <x-ui.breadcrumbs :items="[['label' => 'Blog']]" />
            <x-ui.section-heading class="mt-6" level="1" eyebrow="Career resources" :title="$pageHeading" :description="$pageDescription" />
            <form method="GET" action="{{ url()->current() }}" class="mt-8 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 {{ $isArchive ? 'lg:grid-cols-4' : 'lg:grid-cols-6' }}">
                <label class="sm:col-span-2 lg:col-span-2"><span class="sr-only">Search articles</span><input class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600" type="search" name="keyword" value="{{ $filters['keyword'] }}" placeholder="Search career advice, interviews, skills…"></label>
                @unless($isArchive)
                    <label><span class="sr-only">Category</span><select class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600" name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected($filters['category'] === $category->slug)>{{ $category->name }}</option>@endforeach</select></label>
                    <label><span class="sr-only">Tag</span><select class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600" name="tag"><option value="">All topics</option>@foreach($tags as $tag)<option value="{{ $tag->slug }}" @selected($filters['tag'] === $tag->slug)>{{ $tag->name }}</option>@endforeach</select></label>
                @endunless
                <label><span class="sr-only">Sort articles</span><select class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600" name="sort"><option value="latest" @selected($filters['sort'] === 'latest')>Newest first</option><option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option></select></label>
                <label><span class="sr-only">Layout</span><select class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600" name="view"><option value="grid" @selected($filters['view'] === 'grid')>Grid view</option><option value="list" @selected($filters['view'] === 'list')>List view</option></select></label>
                <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-full"><button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 text-sm font-bold text-white hover:bg-brand-800">Search articles</button><a href="{{ $filterResetUrl }}" class="text-sm font-semibold text-slate-600 hover:text-brand-700">Clear filters</a></div>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-7xl space-y-10 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        @if($featuredArticle)
            <article class="grid overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm lg:grid-cols-[1.15fr_1fr]">
                <a href="{{ route('blog.show', $featuredArticle) }}" class="block min-h-64 bg-brand-50 lg:min-h-80" aria-label="Read {{ $featuredArticle->title }}">
                    @if($featuredArticle->featuredImageUrl())<img src="{{ $featuredArticle->featuredImageUrl() }}" alt="{{ $featuredArticle->featured_image_alt ?: $featuredArticle->title }}" class="h-full min-h-64 w-full object-cover lg:min-h-80">@else<div class="grid h-full min-h-64 place-items-center text-6xl text-brand-300 lg:min-h-80" aria-hidden="true">✦</div>@endif
                </a>
                <div class="flex flex-col justify-center p-6 sm:p-9 lg:p-12">
                    <p class="text-xs font-extrabold uppercase tracking-[.16em] text-brand-700">Featured article</p>
                    @if($featuredArticle->categories->isNotEmpty())<a href="{{ route('blog.categories.show', $featuredArticle->categories->first()) }}" class="mt-4 text-sm font-bold text-brand-700">{{ $featuredArticle->categories->first()->name }}</a>@endif
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl"><a class="hover:text-brand-700" href="{{ route('blog.show', $featuredArticle) }}">{{ $featuredArticle->title }}</a></h2>
                    <p class="mt-4 leading-7 text-slate-600">{{ $featuredArticle->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($featuredArticle->body), 220) }}</p>
                    <p class="mt-5 text-sm text-slate-500">{{ $featuredArticle->published_at?->format('F j, Y') }}@if($featuredArticle->author) · {{ $featuredArticle->author->blogAuthorName() }}@endif</p>
                    <a href="{{ route('blog.show', $featuredArticle) }}" class="mt-6 inline-flex items-center gap-2 font-bold text-brand-700 hover:text-brand-900">Read featured article <span aria-hidden="true">→</span></a>
                </div>
            </article>
        @endif

        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div>
                <div class="mb-5 flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-brand-700">{{ $isArchive ? 'Browse the archive' : 'Fresh perspectives' }}</p><h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-950">{{ $isArchive ? $pageHeading : 'Latest articles' }}</h2></div><p class="text-sm text-slate-500">{{ $articles->total() }} {{ \Illuminate\Support\Str::plural('article', $articles->total()) }}</p></div>
                @if($articles->isEmpty())
                    <x-ui.empty-state title="No articles found" description="Try a different keyword or clear the filters to see all published articles." :action="$filterResetUrl" action-text="Clear filters" icon="⌕" />
                @else
                    <div class="grid gap-5 {{ $filters['view'] === 'list' ? 'grid-cols-1' : 'sm:grid-cols-2' }}">@foreach($articles as $article)<x-blog-card :article="$article" :layout="$filters['view']" />@endforeach</div>
                    <div class="mt-8">{{ $articles->links() }}</div>
                @endif
            </div>

            <aside class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-5"><h2 class="font-extrabold text-slate-950">Popular categories</h2><div class="mt-4 flex flex-wrap gap-2">@forelse($categories as $category)<a href="{{ route('blog.categories.show', $category) }}" class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-brand-50 hover:text-brand-800">{{ $category->name }}</a>@empty<p class="text-sm text-slate-500">Categories will appear as articles are published.</p>@endforelse</div></section>
                <section class="rounded-2xl border border-slate-200 bg-white p-5"><h2 class="font-extrabold text-slate-950">Browse topics</h2><div class="mt-4 flex flex-wrap gap-2">@forelse($tags as $tag)<a href="{{ route('blog.tags.show', $tag) }}" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-brand-200 hover:text-brand-800">#{{ $tag->name }}</a>@empty<p class="text-sm text-slate-500">Topics will appear as articles are published.</p>@endforelse</div></section>
                <section class="rounded-2xl bg-brand-800 p-5 text-white"><p class="text-xs font-bold uppercase tracking-[.14em] text-brand-200">Your next move</p><h2 class="mt-2 text-xl font-extrabold">Put good advice into action</h2><p class="mt-2 text-sm leading-6 text-blue-100">Explore current opportunities from employers across Rajasthan.</p><a href="{{ route('jobs.index') }}" class="mt-4 inline-flex font-bold text-white underline decoration-white/50 underline-offset-4">Browse jobs <span class="ml-2" aria-hidden="true">→</span></a></section>
            </aside>
        </div>
    </section>
@endsection
