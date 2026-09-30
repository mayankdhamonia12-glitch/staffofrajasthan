@props(['eyebrow' => null, 'title', 'description' => null, 'link' => null, 'linkText' => 'Explore all', 'level' => 2])
<div {{ $attributes->class(['flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="max-w-2xl">
        @if ($eyebrow)<p class="text-sm font-bold uppercase tracking-[.14em] text-brand-700">{{ $eyebrow }}</p>@endif
        @if ((int) $level === 1)<h1 class="{{ $eyebrow ? 'mt-2' : '' }} text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $title }}</h1>@else<h2 class="{{ $eyebrow ? 'mt-2' : '' }} text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $title }}</h2>@endif
        @if ($description)<p class="mt-2 text-base leading-7 text-slate-600">{{ $description }}</p>@endif
    </div>
    @if ($link)<x-ui.button :href="$link" variant="ghost" size="sm">{{ $linkText }} <span aria-hidden="true">→</span></x-ui.button>@endif
</div>
