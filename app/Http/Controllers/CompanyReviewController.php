<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\User;
use App\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanyReviewController extends Controller
{
    public function index(Request $request): View
    {
        $candidate = $this->candidate($request);
        $reviews = $candidate->companyReviews()->with('company')->latest()->paginate(15);

        return view('candidate.reviews.index', compact('reviews'));
    }

    public function store(Request $request): RedirectResponse
    {
        $candidate = $this->candidate($request);
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:20', 'max:5000'],
        ]);
        $company = Company::query()->publiclyVisible()->whereKey($data['company_id'])->firstOrFail();
        $hasApplication = $candidate->jobApplications()
            ->whereHas('job', fn (Builder $jobs) => $jobs->where('company_id', $company->getKey()))
            ->exists();
        abort_unless($hasApplication, 403, 'Only a candidate who applied to this company can submit a review.');

        DB::transaction(function () use ($candidate, $company, $data): void {
            Company::query()->whereKey($company->getKey())->lockForUpdate()->firstOrFail();
            if ($company->reviews()->where('user_id', $candidate->getKey())->exists()) {
                throw ValidationException::withMessages(['company_id' => 'You may submit one review per company.']);
            }

            $company->reviews()->create([
                'user_id' => $candidate->getKey(),
                'rating' => (int) $data['rating'],
                'title' => trim($data['title']),
                'body' => trim($data['body']),
                'status' => ReviewStatus::Pending,
            ]);
        });

        return back()->with('status', 'Your review was submitted for moderation. It will not be public unless approved.');
    }

    public function moderationIndex(Request $request): View
    {
        $this->moderator($request);
        $reviews = CompanyReview::query()
            ->where('status', ReviewStatus::Pending->value)
            ->with(['company', 'candidate'])
            ->oldest()
            ->paginate(20);

        return view('admin.company-reviews.index', compact('reviews'));
    }

    public function moderate(Request $request, CompanyReview $companyReview): RedirectResponse
    {
        $moderator = $this->moderator($request);
        $data = $request->validate(['status' => ['required', Rule::in([ReviewStatus::Approved->value, ReviewStatus::Rejected->value])]]);

        DB::transaction(function () use ($moderator, $companyReview, $data): void {
            $review = CompanyReview::query()->with('company')->whereKey($companyReview->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($review->status === ReviewStatus::Pending, 409, 'Only pending reviews can be moderated.');
            abort_unless((int) $review->company->user_id !== (int) $moderator->getKey(), 403, 'A company owner cannot moderate reviews of their own company.');
            $review->update([
                'status' => ReviewStatus::from($data['status']),
                'moderated_by_user_id' => $moderator->getKey(),
                'moderated_at' => now(),
            ]);
        });

        return back()->with('status', 'Review moderation saved.');
    }

    private function candidate(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isCandidate(), 403);

        return $user;
    }

    private function moderator(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isOwner()), 403);

        return $user;
    }
}
