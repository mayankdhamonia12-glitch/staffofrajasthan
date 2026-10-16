@props(['conversation', 'messages', 'user', 'routePrefix'])

@php
    $application = $conversation->application;
    $company = $application->job->company;
    $candidateProfile = $conversation->candidate->candidateProfile;
    $isCandidate = $user->isCandidate();
    $otherName = $isCandidate ? $conversation->employer->name : $conversation->candidate->name;
@endphp

<x-ui.breadcrumbs :items="[['label' => 'Messages', 'url' => route($routePrefix.'messages.index')], ['label' => $otherName]]" />
@if(session('status'))<x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>@endif
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <header class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-4 sm:p-5">
        <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-blue-50 font-bold text-brand-700">
            @if($isCandidate && $company->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) }}" alt="" class="h-full w-full object-cover">
            @elseif(!$isCandidate && $candidateProfile?->is_public && $candidateProfile->slug && $candidateProfile->profile_photo_path)<img src="{{ route('candidates.photo', $candidateProfile->slug) }}" alt="" class="h-full w-full object-cover">
            @else{{ mb_substr($isCandidate ? $company->name : $otherName, 0, 1) }}@endif
        </div>
        <div class="min-w-0 flex-1"><h2 class="truncate text-lg font-extrabold text-slate-950">{{ $otherName }}</h2><p class="truncate text-sm text-slate-500">{{ $company->name }} · {{ $application->job->title }}</p></div>
        <p class="text-xs font-medium text-slate-500">Updates automatically every 15 seconds</p>
    </header>

    <div id="message-thread" data-poll-url="{{ route($routePrefix.'messages.updates', $conversation) }}" data-current-user-id="{{ $user->getKey() }}" data-after-id="{{ $messages->last()?->getKey() ?? 0 }}" class="max-h-[60vh] min-h-72 space-y-3 overflow-y-auto bg-slate-50 p-4 sm:p-6" aria-live="polite" aria-label="Conversation messages">
        @forelse($messages as $message)
            <article data-message-id="{{ $message->getKey() }}" @class(['ml-auto max-w-[90%] sm:max-w-[75%]' => $message->sender_user_id === $user->getKey(), 'mr-auto max-w-[90%] sm:max-w-[75%]' => $message->sender_user_id !== $user->getKey()])>
                <div @class(['rounded-2xl px-4 py-3 shadow-sm', 'rounded-br-md bg-brand-700 text-white' => $message->sender_user_id === $user->getKey(), 'rounded-bl-md border border-slate-200 bg-white text-slate-800' => $message->sender_user_id !== $user->getKey()])>
                    <p class="whitespace-pre-wrap break-words text-sm leading-6">{{ $message->body }}</p>
                </div>
                <p @class(['mt-1 text-[11px] text-slate-500', 'text-right' => $message->sender_user_id === $user->getKey()])>{{ $message->sender_user_id === $user->getKey() ? 'You' : $otherName }} · {{ $message->created_at?->format('M j, g:i a') }}</p>
            </article>
        @empty
            <p id="message-empty" class="py-16 text-center text-sm text-slate-500">This conversation is ready. Send a message to get things started.</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route($routePrefix.'messages.send', $conversation) }}" class="border-t border-slate-100 p-4 sm:p-5">
        @csrf
        <label for="message-body" class="sr-only">Your message</label>
        <textarea id="message-body" name="body" rows="3" maxlength="5000" required placeholder="Write a message…" class="block w-full resize-y rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600">{{ old('body') }}</textarea>
        @error('body')<p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3"><p class="text-xs text-slate-500">Messages are private to the candidate and employer for this application.</p><button class="rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-800">Send message</button></div>
    </form>
</section>

<script>
(() => {
    const thread = document.getElementById('message-thread');
    if (!thread) return;
    let afterId = Number(thread.dataset.afterId || 0);
    const currentUserId = Number(thread.dataset.currentUserId);
    const escapeText = (value) => String(value ?? '');
    const scrollToLatest = () => { thread.scrollTop = thread.scrollHeight; };
    scrollToLatest();

    async function refreshMessages() {
        try {
            const separator = thread.dataset.pollUrl.includes('?') ? '&' : '?';
            const response = await fetch(`${thread.dataset.pollUrl}${separator}after_id=${afterId}`, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) return;
            const payload = await response.json();
            for (const message of payload.messages ?? []) {
                if (Number(message.id) <= afterId) continue;
                document.getElementById('message-empty')?.remove();
                const article = document.createElement('article');
                const own = Number(message.sender_id) === currentUserId;
                article.dataset.messageId = String(message.id);
                article.className = own ? 'ml-auto max-w-[90%] sm:max-w-[75%]' : 'mr-auto max-w-[90%] sm:max-w-[75%]';
                const bubble = document.createElement('div');
                bubble.className = own ? 'rounded-2xl rounded-br-md bg-brand-700 px-4 py-3 text-white shadow-sm' : 'rounded-2xl rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-slate-800 shadow-sm';
                const body = document.createElement('p');
                body.className = 'whitespace-pre-wrap break-words text-sm leading-6';
                body.textContent = escapeText(message.body);
                bubble.append(body);
                const meta = document.createElement('p');
                meta.className = own ? 'mt-1 text-right text-[11px] text-slate-500' : 'mt-1 text-[11px] text-slate-500';
                meta.textContent = `${own ? 'You' : escapeText(message.sender_name)} · ${escapeText(message.time)}`;
                article.append(bubble, meta);
                thread.append(article);
                afterId = Number(message.id);
                thread.dataset.afterId = String(afterId);
            }
            if ((payload.messages ?? []).length) scrollToLatest();
        } catch (_) {
            // Keep the thread usable when a temporary network interruption occurs; retry on the next interval.
        }
    }

    window.setInterval(refreshMessages, 15000);
})();
</script>
