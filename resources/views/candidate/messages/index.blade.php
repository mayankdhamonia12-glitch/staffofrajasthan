<x-candidate-dashboard-layout title="Messages" active="messages" subtitle="Private conversations about your job applications.">
    <div><p class="text-sm font-semibold text-brand-700">Application conversations</p><h2 class="mt-1 text-2xl font-extrabold">Messages</h2><p class="mt-1 text-sm text-slate-500">Only you and the employer for each application can access its conversation.</p></div>
    @if($conversations->isEmpty())<x-ui.empty-state title="No conversations yet" description="You can start a private conversation from one of your applied jobs. Messages are only available in the context of a real application." :action="route('candidate.applied-jobs.index')" action-text="View applied jobs" />
    @else
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><ul class="divide-y divide-slate-100">
            @foreach($conversations as $conversation)
                @php($application = $conversation->application)
                <li><a href="{{ route('candidate.messages.show', $conversation) }}" class="flex items-center gap-4 p-4 transition hover:bg-slate-50 sm:p-5">
                    <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-blue-50 font-bold text-brand-700">@if($application->job->company->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($application->job->company->logo_path) }}" alt="" class="h-full w-full object-cover">@else{{ mb_substr($application->job->company->name, 0, 1) }}@endif</span>
                    <span class="min-w-0 flex-1"><span class="flex flex-wrap items-center gap-2"><span class="font-bold text-slate-900">{{ $application->job->company->name }}</span><span class="text-xs text-slate-500">{{ $application->job->title }}</span></span><span class="mt-1 block truncate text-sm text-slate-600">{{ $conversation->latestMessage?->body ?? 'No messages yet — start the conversation' }}</span></span>
                    <span class="shrink-0 text-right">@if($conversation->last_message_at)<span class="block text-xs text-slate-500">{{ $conversation->last_message_at->diffForHumans() }}</span>@endif @if($conversation->unread_count)<span class="mt-1 inline-flex min-w-6 justify-center rounded-full bg-blue-100 px-2 py-1 text-xs font-bold text-blue-800">{{ $conversation->unread_count }}</span>@endif</span>
                </a></li>
            @endforeach
        </ul><div class="border-t border-slate-100 p-4">{{ $conversations->links() }}</div></section>
    @endif
</x-candidate-dashboard-layout>
