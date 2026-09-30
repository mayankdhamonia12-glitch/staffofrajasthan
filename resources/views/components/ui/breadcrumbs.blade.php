@props(['items' => []])
<nav aria-label="Breadcrumb" class="text-sm text-slate-500">
    <ol class="flex flex-wrap items-center gap-2">
        <li><a class="rounded-sm hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600" href="{{ route('home') }}">Home</a></li>
        @foreach ($items as $item)
            <li aria-hidden="true">/</li>
            <li>@if (! $loop->last && isset($item['url']))<a class="rounded-sm hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600" href="{{ $item['url'] }}">{{ $item['label'] }}</a>@else<span aria-current="page" class="font-medium text-slate-800">{{ $item['label'] }}</span>@endif</li>
        @endforeach
    </ol>
</nav>
