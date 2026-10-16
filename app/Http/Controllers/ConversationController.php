<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\JobApplication;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->participant($request);
        $conversations = $this->participantConversations($user)
            ->with([
                'application.job.company',
                'candidate.candidateProfile' => fn ($query) => $query->where('is_public', true),
                'employer',
                'latestMessage',
            ])
            ->withCount(['messages as unread_count' => fn (Builder $messages) => $messages
                ->whereNull('read_at')->where('sender_user_id', '!=', $user->getKey())])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view($user->isCandidate() ? 'candidate.messages.index' : 'employer.messages.index', compact('conversations', 'user'));
    }

    public function start(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        $user = $this->participant($request);
        $application = JobApplication::query()->with(['job.company'])->whereKey($jobApplication->getKey())->firstOrFail();
        $isCandidate = $user->isCandidate() && $application->user_id === $user->getKey();
        $isEmployer = $user->isEmployer() && $application->job->company->user_id === $user->getKey();
        abort_unless($isCandidate || $isEmployer, 404);

        $conversation = DB::transaction(function () use ($application): Conversation {
            $lockedApplication = JobApplication::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();

            return Conversation::query()->firstOrCreate(
                ['job_application_id' => $lockedApplication->getKey()],
                ['candidate_user_id' => $lockedApplication->user_id, 'employer_user_id' => $application->job->company->user_id],
            );
        });

        return redirect()->route($this->routePrefix($user).'messages.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $user = $this->participant($request);
        $conversation = $this->participantConversations($user)
            ->with([
                'application.job.company',
                'candidate.candidateProfile' => fn ($query) => $query->where('is_public', true),
                'employer',
            ])
            ->whereKey($conversation->getKey())
            ->firstOrFail();

        $conversation->messages()->where('sender_user_id', '!=', $user->getKey())->whereNull('read_at')->update(['read_at' => now()]);
        $messages = $conversation->messages()->with('sender')->latest('id')->limit(50)->get()->reverse()->values();

        return view($user->isCandidate() ? 'candidate.messages.show' : 'employer.messages.show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'user' => $user,
            'routePrefix' => $this->routePrefix($user),
        ]);
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $this->participant($request);
        $conversation = $this->participantConversations($user)->whereKey($conversation->getKey())->firstOrFail();

        if (! RateLimiter::attempt('message-send:'.$user->getKey(), 20, static fn (): bool => true, 60)) {
            abort(429, 'Please wait before sending more messages.');
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $body = trim($data['body']);
        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'Write a message before sending.']);
        }

        DB::transaction(function () use ($conversation, $user, $body): void {
            $locked = Conversation::query()
                ->whereKey($conversation->getKey())
                ->where(fn (Builder $query) => $query->where('candidate_user_id', $user->getKey())->orWhere('employer_user_id', $user->getKey()))
                ->lockForUpdate()
                ->firstOrFail();
            $locked->messages()->create(['sender_user_id' => $user->getKey(), 'body' => $body]);
            $locked->update(['last_message_at' => now()]);
        });

        return back()->with('status', 'Message sent.');
    }

    public function updates(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $this->participant($request);
        $conversation = $this->participantConversations($user)->whereKey($conversation->getKey())->firstOrFail();
        $data = $request->validate(['after_id' => ['nullable', 'integer', 'min:0']]);
        $afterId = (int) ($data['after_id'] ?? 0);

        $messages = $conversation->messages()->with('sender')->where('id', '>', $afterId)->orderBy('id')->limit(100)->get();
        $conversation->messages()->whereKey($messages->pluck('id'))
            ->where('sender_user_id', '!=', $user->getKey())->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn (Message $message): array => [
                'id' => $message->getKey(),
                'sender_id' => $message->sender_user_id,
                'sender_name' => $message->sender->name,
                'body' => $message->body,
                'created_at' => $message->created_at?->toIso8601String(),
                'time' => $message->created_at?->format('M j, g:i a'),
            ])->values(),
        ]);
    }

    private function participant(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isCandidate() || $user->isEmployer()), 403);

        return $user;
    }

    /** @return Builder<Conversation> */
    private function participantConversations(User $user): Builder
    {
        return Conversation::query()->where($user->isCandidate() ? 'candidate_user_id' : 'employer_user_id', $user->getKey());
    }

    private function routePrefix(User $user): string
    {
        return $user->isCandidate() ? 'candidate.' : 'employer.';
    }
}
