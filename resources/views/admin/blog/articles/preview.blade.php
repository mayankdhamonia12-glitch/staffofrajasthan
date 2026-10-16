<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-xl font-semibold leading-tight text-gray-800">Private article preview</h2><p class="mt-1 text-sm text-gray-500">Visible only to authorized blog managers.</p></div><a href="{{ route('admin.blog.articles.edit', $article) }}" class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-bold text-white">Edit article</a></div></x-slot>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">This is a private preview of a {{ $article->status->value }} article. It is not indexed or available on public blog routes.</div>
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
            <div class="flex flex-wrap gap-2">@foreach($article->categories as $category)<span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $category->name }}</span>@endforeach</div>
            <h1 class="mt-4 text-3xl font-extrabold text-slate-950">{{ $article->title }}</h1>
            @if($article->excerpt)<p class="mt-3 text-lg text-slate-600">{{ $article->excerpt }}</p>@endif
            @if($article->featuredImageUrl())<img class="mt-6 max-h-96 w-full rounded-2xl object-cover" src="{{ $article->featuredImageUrl() }}" alt="{{ $article->featured_image_alt ?: $article->title }}">@endif
            <div class="article-prose mt-8 text-slate-700">{!! $article->renderedBody() !!}</div>
        </article>
    </div>
</x-app-layout>
