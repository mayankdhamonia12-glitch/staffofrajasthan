<?php

use App\MeetingStatus;
use App\Models\CompanyReview;
use App\Models\Conversation;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\Meeting;
use App\Models\Message;
use App\Models\User;
use App\ReviewStatus;
use Illuminate\Support\Carbon;

beforeEach(fn () => $this->withoutVite());

function step11TestJob(User $employer, string $slug): Job
{
    $company = $employer->companies()->create(['name' => 'Step 11 '.$slug, 'slug' => 'step-11-'.$slug, 'is_visible' => true]);
    $category = JobCategory::firstOrCreate(['name' => 'Step 11 category'], ['slug' => 'step-11-category']);
    $industry = Industry::firstOrCreate(['name' => 'Step 11 industry'], ['slug' => 'step-11-industry']);
    $location = Location::firstOrCreate(['name' => 'Step 11 Jaipur', 'state' => 'Rajasthan'], ['slug' => 'step-11-jaipur']);

    return $company->jobs()->create([
        'job_category_id' => $category->id, 'industry_id' => $industry->id, 'location_id' => $location->id,
        'title' => 'Step 11 role '.$slug, 'slug' => 'step-11-role-'.$slug,
        'description' => 'A real job used for Step 11 feature tests.', 'responsibilities' => 'Work with the team.',
        'requirements' => 'Relevant experience.', 'employment_type' => 'full_time', 'experience_level' => 'Mid level',
        'salary_currency' => 'INR', 'salary_period' => 'yearly', 'status' => 'published', 'published_at' => now()->subDay(),
    ]);
}

function step11TestApplication(User $candidate, Job $job): JobApplication
{
    return $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'applied']);
}

function step11TestMeetingPayload(string $when, array $extra = []): array
{
    return array_merge(['title' => 'Initial interview', 'starts_at' => $when, 'duration_minutes' => 45,
        'type' => 'online', 'meeting_url' => 'https://meet.example.test/private-room', 'agenda' => 'Discuss the role.'], $extra);
}

test('messaging is bound to an actual application, supports both participants and keeps content private', function () {
    $employer = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $candidate = User::factory()->candidate()->create();
    $otherCandidate = User::factory()->candidate()->create();
    $application = step11TestApplication($candidate, step11TestJob($employer, 'messages'));

    $this->actingAs($candidate)->post(route('candidate.messages.start', $application))->assertRedirect();
    $conversation = Conversation::query()->firstOrFail();
    expect($conversation->candidate_user_id)->toBe($candidate->id)->and($conversation->employer_user_id)->toBe($employer->id);
    $this->post(route('candidate.messages.start', $application))->assertRedirect(route('candidate.messages.show', $conversation));
    expect(Conversation::query()->count())->toBe(1);
    $this->post(route('candidate.messages.send', $conversation), ['body' => '<script>alert("x")</script>'])->assertRedirect();
    $message = Message::query()->firstOrFail();

    $this->actingAs($employer)->get(route('employer.messages.show', $conversation))
        ->assertOk()->assertSee(e('<script>alert("x")</script>'), false)->assertDontSee('<script>alert("x")</script>', false);
    expect($message->fresh()->read_at)->not->toBeNull();
    $this->post(route('employer.messages.send', $conversation), ['body' => 'Thanks for applying.'])->assertRedirect();
    $this->get(route('employer.messages.updates', ['conversation' => $conversation, 'after_id' => $message->id]))
        ->assertOk()->assertJsonPath('messages.0.body', 'Thanks for applying.');

    $this->actingAs($otherCandidate)->get(route('candidate.messages.show', $conversation))->assertNotFound();
    $this->actingAs($otherEmployer)->get(route('employer.messages.show', $conversation))->assertNotFound();
    $this->actingAs($otherCandidate)->post(route('candidate.messages.start', $application))->assertNotFound();
    expect(Message::query()->count())->toBe(2)->and(Conversation::query()->count())->toBe(1);
});

test('employers can only start conversations from applications to jobs they own', function () {
    $employer = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $candidate = User::factory()->candidate()->create();
    $owned = step11TestApplication($candidate, step11TestJob($employer, 'owned-conversation'));
    $other = step11TestApplication($candidate, step11TestJob($otherEmployer, 'other-conversation'));

    $this->actingAs($employer)->post(route('employer.messages.start', $owned))->assertRedirect();
    $this->post(route('employer.messages.start', $other))->assertNotFound();
    expect(Conversation::query()->count())->toBe(1);
});

test('meeting workflow validates ownership, stores UTC, records history and notifies both participants safely', function () {
    $employer = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $candidate = User::factory()->candidate()->create();
    $otherCandidate = User::factory()->candidate()->create();
    $application = step11TestApplication($candidate, step11TestJob($employer, 'meeting'));
    $future = Carbon::now('Asia/Kolkata')->addDays(3)->startOfMinute()->format('Y-m-d\TH:i');
    $this->actingAs($otherEmployer)->post(route('employer.meetings.store', $application), step11TestMeetingPayload($future))->assertNotFound();
    $this->actingAs($employer)->post(route('employer.meetings.store', $application), step11TestMeetingPayload($future))->assertRedirect();
    $meeting = Meeting::query()->firstOrFail();
    expect($meeting->timezone)->toBe('Asia/Kolkata')->and($meeting->status)->toBe(MeetingStatus::Scheduled)
        ->and($meeting->starts_at->timezone('Asia/Kolkata')->format('Y-m-d H:i'))->toBe(str_replace('T', ' ', $future));
    expect($candidate->notifications()->count())->toBe(1)->and($employer->notifications()->count())->toBe(1)
        ->and(json_encode($candidate->notifications()->first()->data))->not->toContain('private-room');
    $this->actingAs($candidate)->get(route('candidate.meetings.show', $meeting))->assertOk()->assertSee('private-room');
    $this->actingAs($otherCandidate)->get(route('candidate.meetings.show', $meeting))->assertNotFound();
    $this->actingAs($otherEmployer)->get(route('employer.meetings.show', $meeting))->assertNotFound();

    $newTime = Carbon::now('Asia/Kolkata')->addDays(5)->startOfMinute()->format('Y-m-d\TH:i');
    $this->actingAs($employer)->patch(route('employer.meetings.reschedule', $meeting), step11TestMeetingPayload($newTime, ['title' => 'Final interview']))->assertRedirect();
    expect($meeting->fresh()->title)->toBe('Final interview')->and($meeting->events()->count())->toBe(2)
        ->and($candidate->notifications()->count())->toBe(2);
    $this->patch(route('employer.meetings.cancel', $meeting))->assertRedirect();
    $this->patch(route('employer.meetings.cancel', $meeting))->assertRedirect();
    expect($meeting->fresh()->status)->toBe(MeetingStatus::Cancelled)->and($meeting->events()->count())->toBe(3)
        ->and($candidate->notifications()->count())->toBe(3)->and($employer->notifications()->count())->toBe(3);
    $this->actingAs($candidate)->get(route('candidate.meetings.show', $meeting))->assertOk()->assertDontSee('private-room');
});

test('meeting validation rejects past dates, malformed links and short durations', function () {
    $employer = User::factory()->employer()->create();
    $candidate = User::factory()->candidate()->create();
    $application = step11TestApplication($candidate, step11TestJob($employer, 'invalid-meeting'));
    $past = Carbon::now('Asia/Kolkata')->subDay()->format('Y-m-d\TH:i');
    $future = Carbon::now('Asia/Kolkata')->addDays(2)->format('Y-m-d\TH:i');

    $this->actingAs($employer)->post(route('employer.meetings.store', $application), step11TestMeetingPayload($past))->assertSessionHasErrors('starts_at');
    $this->post(route('employer.meetings.store', $application), step11TestMeetingPayload($future, ['meeting_url' => 'javascript://example.test']))->assertSessionHasErrors('meeting_url');
    $this->post(route('employer.meetings.store', $application), step11TestMeetingPayload($future, ['duration_minutes' => 5]))->assertSessionHasErrors('duration_minutes');
    expect(Meeting::query()->count())->toBe(0)->and($candidate->notifications()->count())->toBe(0);
});

test('reviews require a real application, stay candidate-private while pending and become public only when approved', function () {
    $employer = User::factory()->employer()->create();
    $company = $employer->companies()->create(['name' => 'Reviewed Company', 'slug' => 'reviewed-company', 'is_visible' => true]);
    $candidate = User::factory()->candidate()->create();
    $otherCandidate = User::factory()->candidate()->create();
    $admin = User::factory()->admin()->create();
    $payload = ['company_id' => $company->id, 'rating' => 5, 'title' => 'Thoughtful interview process', 'body' => 'The hiring team communicated clearly throughout the process.'];
    $this->actingAs($candidate)->post(route('candidate.reviews.store'), $payload)->assertForbidden();

    $job = $company->jobs()->create(['title' => 'Eligible role', 'slug' => 'eligible-reviewed-role', 'description' => 'Role details.',
        'responsibilities' => 'Responsibilities.', 'requirements' => 'Requirements.', 'employment_type' => 'full_time',
        'experience_level' => 'Mid level', 'salary_currency' => 'INR', 'salary_period' => 'yearly', 'status' => 'published', 'published_at' => now()->subDay()]);
    step11TestApplication($candidate, $job);
    $this->post(route('candidate.reviews.store'), $payload)->assertRedirect();
    $review = CompanyReview::query()->firstOrFail();
    expect($review->status)->toBe(ReviewStatus::Pending);
    $this->get(route('companies.show', $company))->assertOk()->assertSee('No approved reviews yet')->assertDontSee($payload['title'])->assertDontSee($payload['body']);
    $this->actingAs($candidate)->get(route('candidate.reviews.index'))->assertOk()->assertSee($payload['title']);
    $this->actingAs($otherCandidate)->get(route('candidate.reviews.index'))->assertOk()->assertDontSee($payload['title'])->assertDontSee($payload['body']);
    $this->actingAs($employer)->get(route('candidate.reviews.index'))->assertForbidden();
    $this->actingAs($candidate)->get(route('companies.show', $company))->assertOk()->assertDontSee($payload['body']);
    $this->post(route('candidate.reviews.store'), $payload)->assertSessionHasErrors('company_id');
    expect(CompanyReview::query()->count())->toBe(1);

    $this->actingAs($admin)->get(route('admin.company-reviews.index'))->assertOk()->assertSee($payload['title']);
    $this->patch(route('admin.company-reviews.moderate', $review), ['status' => 'approved', 'moderation_note' => 'Review is appropriate and based on an application.'])->assertRedirect();
    expect($review->fresh()->status)->toBe(ReviewStatus::Approved);
    $this->get(route('companies.show', $company))->assertOk()->assertSee($payload['title'])->assertSee('5.0')->assertSee('1 approved review');
});

test('company owners cannot moderate their own company reviews', function () {
    $owner = User::factory()->owner()->create();
    $candidate = User::factory()->candidate()->create();
    $company = $owner->companies()->create(['name' => 'Owner company', 'slug' => 'owner-company', 'is_visible' => true]);
    $review = $company->reviews()->create(['user_id' => $candidate->id, 'rating' => 4, 'title' => 'Pending', 'body' => 'Candidate feedback waiting on moderation.', 'status' => ReviewStatus::Pending]);

    $this->actingAs($owner)->patch(route('admin.company-reviews.moderate', $review), ['status' => 'approved'])->assertForbidden();
    expect($review->fresh()->status)->toBe(ReviewStatus::Pending);
});
