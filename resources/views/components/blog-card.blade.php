@props(['article', 'layout' => 'grid'])
@php($imageUrl = $article->featuredImageUrl())
<x-ui.card :interactive="true" class="overflow-hidden !p-0 {{ $layout === 'list' ? 'sm:grid sm:grid-cols-[16rem_minmax(0,1fr)]' : '' }}">
    @if ($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $article->featured_image_alt ?: $article->title }}" class="{{ $layout === 'list' ? 'aspect-[16/9] h-full min-h-48 w-full object-cover' : 'aspect-[16/9] w-full object-cover' }}">@else<div class="grid {{ $layout === 'list' ? 'min-h-48' : 'aspect-[16/9]' }} place-items-center bg-gradient-to-br from-brand-50 to-slate-100 text-brand-700" aria-hidden="true"><span class="text-4xl">✦</span></div>@endif
    <div class="p-5">
        @if ($article->categories->isNotEmpty())<a href="{{ route('blog.categories.show', $article->categories->first()) }}" class="text-xs font-bold uppercase tracking-wide text-brand-700">{{ $article->categories->first()->name }}</a>@endif
        <p class="mt-3 text-xs font-medium text-slate-500">{{ $article->published_at?->format('M j, Y') }}@if($article->author) · {{ $article->author->blogAuthorName() }}@endif</p>
        <h3 class="mt-2 text-lg font-bold text-slate-950"><a href="{{ route('blog.show', $article) }}" class="hover:text-brand-700">{{ $article->title }}</a></h3>
        <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->body), 180) }}</p>
        <a href="{{ route('blog.show', $article) }}" class="mt-4 inline-flex rounded-sm text-sm font-semibold text-brand-700 hover:text-brand-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">Read article <span class="ml-1" aria-hidden="true">→</span></a>
    </div>
</x-ui.card>
