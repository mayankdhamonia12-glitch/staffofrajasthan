<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-xl font-semibold leading-tight text-gray-800">Blog categories &amp; tags</h2><p class="mt-1 text-sm text-gray-500">Taxonomy is shared by articles and public archives. Deleting a term keeps articles but removes its association.</p></div>
            <a href="{{ route('admin.blog.articles.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">Back to articles</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        @if(session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[.14em] text-blue-700">Organize the library</p><h3 class="mt-1 text-2xl font-extrabold text-slate-950">Categories</h3></div><p class="text-sm text-slate-500">{{ $categories->count() }} total</p></div>
            <form method="POST" action="{{ route('admin.blog.categories.store') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_2fr_auto]">
                @csrf
                <input required name="name" maxlength="120" class="rounded-xl border-slate-300 text-sm" placeholder="Category name">
                <input name="slug" maxlength="140" pattern="[a-z0-9]+(-[a-z0-9]+)*" class="rounded-xl border-slate-300 text-sm" placeholder="Slug (optional)">
                <input name="description" maxlength="2000" class="rounded-xl border-slate-300 text-sm" placeholder="Archive description">
                <button class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-bold text-white">Add category</button>
            </form>
            @if($categories->isEmpty())
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">No categories yet.</p>
            @else
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($categories as $category)
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="mb-3 flex items-center justify-between"><span class="text-xs font-semibold text-slate-500">{{ $category->articles_count }} {{ \Illuminate\Support\Str::plural('article', $category->articles_count) }}</span><a href="{{ route('blog.categories.show', $category) }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-blue-700">View archive ↗</a></div>
                            <form method="POST" action="{{ route('admin.blog.categories.update', $category) }}" class="space-y-3">
                                @csrf @method('PUT')
                                <label class="block"><span class="sr-only">Category name</span><input required name="name" value="{{ $category->name }}" maxlength="120" class="w-full rounded-xl border-slate-300 text-sm"></label>
                                <label class="block"><span class="sr-only">Category slug</span><input name="slug" value="{{ $category->slug }}" maxlength="140" pattern="[a-z0-9]+(-[a-z0-9]+)*" class="w-full rounded-xl border-slate-300 text-sm"></label>
                                <label class="block"><span class="sr-only">Category description</span><textarea name="description" maxlength="2000" rows="2" class="w-full rounded-xl border-slate-300 text-sm" placeholder="Archive description">{{ $category->description }}</textarea></label>
                                <button class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-bold text-white">Save category</button>
                            </form>
                            <form method="POST" action="{{ route('admin.blog.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category from its articles?');" class="mt-3 border-t border-slate-100 pt-3">
                                @csrf @method('DELETE')<button class="text-xs font-semibold text-rose-700 hover:underline">Delete category</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[.14em] text-blue-700">Make topics findable</p><h3 class="mt-1 text-2xl font-extrabold text-slate-950">Tags</h3></div><p class="text-sm text-slate-500">{{ $tags->count() }} total</p></div>
            <form method="POST" action="{{ route('admin.blog.tags.store') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_2fr_auto]">
                @csrf
                <input required name="name" maxlength="100" class="rounded-xl border-slate-300 text-sm" placeholder="Tag name">
                <input name="slug" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" class="rounded-xl border-slate-300 text-sm" placeholder="Slug (optional)">
                <input name="description" maxlength="2000" class="rounded-xl border-slate-300 text-sm" placeholder="Optional description">
                <button class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-bold text-white">Add tag</button>
            </form>
            @if($tags->isEmpty())
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">No tags yet.</p>
            @else
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($tags as $tag)
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="mb-3 flex items-center justify-between"><span class="text-xs font-semibold text-slate-500">{{ $tag->articles_count }} {{ \Illuminate\Support\Str::plural('article', $tag->articles_count) }}</span><a href="{{ route('blog.tags.show', $tag) }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-blue-700">View archive ↗</a></div>
                            <form method="POST" action="{{ route('admin.blog.tags.update', $tag) }}" class="space-y-3">
                                @csrf @method('PUT')
                                <label class="block"><span class="sr-only">Tag name</span><input required name="name" value="{{ $tag->name }}" maxlength="100" class="w-full rounded-xl border-slate-300 text-sm"></label>
                                <label class="block"><span class="sr-only">Tag slug</span><input name="slug" value="{{ $tag->slug }}" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" class="w-full rounded-xl border-slate-300 text-sm"></label>
                                <label class="block"><span class="sr-only">Tag description</span><textarea name="description" maxlength="2000" rows="2" class="w-full rounded-xl border-slate-300 text-sm" placeholder="Optional description">{{ $tag->description }}</textarea></label>
                                <button class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-bold text-white">Save tag</button>
                            </form>
                            <form method="POST" action="{{ route('admin.blog.tags.destroy', $tag) }}" onsubmit="return confirm('Delete this tag from its articles?');" class="mt-3 border-t border-slate-100 pt-3">
                                @csrf @method('DELETE')<button class="text-xs font-semibold text-rose-700 hover:underline">Delete tag</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
