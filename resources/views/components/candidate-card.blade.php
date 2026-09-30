@props(['candidate'])
<x-ui.card :interactive="true" class="h-full">
    <div class="flex items-center gap-4">
        <span aria-hidden="true" class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-brand-50 font-bold text-brand-700">{{ mb_substr($candidate->user->name, 0, 1) }}</span>
        <div class="min-w-0"><h3 class="truncate font-bold text-slate-950">{{ $candidate->user->name }}</h3><p class="mt-1 truncate text-sm text-slate-600">{{ $candidate->headline ?: 'Professional profile' }}</p></div>
    </div>
    @if ($candidate->location)<p class="mt-4 text-sm text-slate-500">{{ $candidate->location }}</p>@endif
    @if ($candidate->skills->isNotEmpty())<div class="mt-4 flex flex-wrap gap-2">@foreach ($candidate->skills->take(4) as $skill)<x-ui.chip>{{ $skill->name }}</x-ui.chip>@endforeach</div>@endif
</x-ui.card>
