@if (session('status'))<x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>@endif
<section class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-semibold text-brand-700">Your inbox</p><h2 class="mt-1 text-2xl font-extrabold">Notifications</h2><p class="mt-1 text-sm text-slate-500">{{ number_format($unreadCount) }} unread {{ \Illuminate\Support\Str::plural('notification', $unreadCount) }}</p></div>@if($unreadCount > 0)<form method="POST" action="{{ $candidate ? route('candidate.notifications.read-all') : route('employer.notifications.read-all') }}">@csrf<button class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Mark all as read</button></form>@endif</section>
@if($notifications->isEmpty())
    <x-ui.empty-state :title="$candidate ? 'There are no candidate notifications yet' : 'There are no employer notifications yet'" description="Job matches and application updates will appear here when they happen." icon="♧" />
@else
    <div class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @foreach($notifications as $notification)
            @php($data = $notification->data)
            <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start {{ $notification->read_at ? '' : 'bg-blue-50/50' }}">
                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-200' : 'bg-brand-600' }}" aria-label="{{ $notification->read_at ? 'Read' : 'Unread' }}"></span>
                <div class="min-w-0 flex-1">
                    @if(($data['event'] ?? null) === 'job_opportunity')
                        <h3 class="font-bold text-slate-950">A new job may be a match</h3><p class="mt-1 text-sm leading-6 text-slate-600"><span class="font-semibold">{{ $data['job_title'] ?? 'A new role' }}</span> at {{ $data['company_name'] ?? 'an employer' }} matches an alert or company you follow.</p>
                    @elseif(($data['event'] ?? null) === 'application_status_changed')
                        <h3 class="font-bold text-slate-950">Application status updated</h3><p class="mt-1 text-sm leading-6 text-slate-600">Your application for <span class="font-semibold">{{ $data['job_title'] ?? 'a job' }}</span> is now <span class="font-semibold">{{ str((string)($data['status'] ?? 'updated'))->replace('_', ' ')->title() }}</span>.</p>
                    @elseif(($data['event'] ?? null) === 'new_job_application')
                        <h3 class="font-bold text-slate-950">New job application</h3><p class="mt-1 text-sm leading-6 text-slate-600">A candidate applied for <span class="font-semibold">{{ $data['job_title'] ?? 'one of your jobs' }}</span>.</p>
                    @else
                        <h3 class="font-bold text-slate-950">Account update</h3><p class="mt-1 text-sm text-slate-600">A new update is available in your account.</p>
                    @endif
                    <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-3">
                    @if(isset($targets[$notification->getKey()]))<a href="{{ $targets[$notification->getKey()] }}" class="text-sm font-semibold text-brand-700 hover:underline">{{ ($data['event'] ?? null) === 'new_job_application' ? 'Review application' : (($data['event'] ?? null) === 'application_status_changed' ? 'View applications' : 'View job') }}</a>@endif
                    @unless($notification->read_at)<form method="POST" action="{{ $candidate ? route('candidate.notifications.read', $notification->getKey()) : route('employer.notifications.read', $notification->getKey()) }}">@csrf @method('PATCH')<button class="text-sm font-semibold text-slate-600 hover:text-slate-950">Mark read</button></form>@endunless
                </div>
            </article>
        @endforeach
    </div>
    <x-ui.pagination :paginator="$notifications" />
@endif
