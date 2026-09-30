@props(['post'])
<x-ui.card :interactive="true" class="overflow-hidden !p-0">
    @if ($post->image)<img src="{{ $post->image }}" alt="{{ $post->image_alt ?? $post->title }}" class="aspect-[16/9] w-full object-cover">@else<div class="grid aspect-[16/9] place-items-center bg-gradient-to-br from-brand-50 to-slate-100 text-brand-700" aria-hidden="true"><span class="text-4xl">✦</span></div>@endif
    <div class="p-5">
        @if ($post->category)<x-ui.badge>{{ $post->category }}</x-ui.badge>@endif
        <p class="mt-3 text-xs font-medium text-slate-500">{{ $post->date }}</p>
        <h3 class="mt-2 text-lg font-bold text-slate-950">{{ $post->title }}</h3>
        <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $post->excerpt }}</p>
        <a href="{{ $post->url }}" class="mt-4 inline-flex rounded-sm text-sm font-semibold text-brand-700 hover:text-brand-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">Read article <span class="ml-1" aria-hidden="true">→</span></a>
    </div>
</x-ui.card>
