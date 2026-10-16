@extends('layouts.public')

@section('title', $author->blogAuthorName().' | Career Articles | Staff of Rajasthan')
@section('description', $author->blog_bio ?: 'Published career and hiring articles by '.$author->blogAuthorName().'.')
@section('canonical', $canonical)
@section('robots', 'index,follow')
@section('structured_data')
    <script type="application/ld+json">@json($profileStructuredData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endsection

@section('content')
    <section class="bg-[#f3f7ff]"><div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8"><x-ui.breadcrumbs :items="[['label' => 'Blog', 'url' => route('blog.index')], ['label' => 'Authors'], ['label' => $author->blogAuthorName()]]" /><div class="mt-7 flex flex-col gap-5 sm:flex-row sm:items-center">@if($author->blogProfileImageUrl())<img src="{{ $author->blogProfileImageUrl() }}" alt="" class="h-24 w-24 rounded-full object-cover shadow-sm">@else<span class="grid h-24 w-24 place-items-center rounded-full bg-brand-100 text-3xl font-extrabold text-brand-800">{{ $author->initials() }}</span>@endif<div><p class="text-xs font-bold uppercase tracking-[.16em] text-brand-700">Article author</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">{{ $author->blogAuthorName() }}</h1>@if($author->blog_bio)<p class="mt-3 max-w-3xl leading-7 text-slate-600">{{ $author->blog_bio }}</p>@endif</div></div></div></section>
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8"><div class="mb-6 flex items-end justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-brand-700">From this contributor</p><h2 class="mt-2 text-2xl font-extrabold text-slate-950">Published articles</h2></div><p class="text-sm text-slate-500">{{ $articles->total() }} {{ \Illuminate\Support\Str::plural('article', $articles->total()) }}</p></div>@if($articles->isEmpty())<x-ui.empty-state title="No published articles" description="There are no public articles to display right now." :action="route('blog.index')" action-text="Browse all articles" icon="✎" />@else<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach($articles as $article)<x-blog-card :article="$article" />@endforeach</div><div class="mt-8">{{ $articles->links() }}</div>@endif</section>
@endsection
