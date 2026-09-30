@props(['title', 'description', 'action' => null, 'actionText' => null, 'icon' => '⌕'])
<div {{ $attributes->class(['rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center']) }}>
    <span aria-hidden="true" class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-brand-50 text-xl font-semibold text-brand-700">{{ $icon }}</span>
    <h3 class="mt-4 text-lg font-bold text-slate-950">{{ $title }}</h3>
    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-600">{{ $description }}</p>
    @if ($action && $actionText)<x-ui.button :href="$action" class="mt-5">{{ $actionText }}</x-ui.button>@endif
</div>
