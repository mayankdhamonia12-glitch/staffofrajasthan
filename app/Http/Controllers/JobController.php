<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JobController extends Controller
{
    public function show(Job $job): View
    {
        abort_unless(Job::query()->publiclyAvailable()->whereKey($job->getKey())->exists(), 404);

        $job->load(['company.industry', 'company.location', 'category', 'industry', 'location', 'skills']);

        $user = request()->user();
        $isCandidate = $user?->isCandidate() ?? false;
        $savedJob = $isCandidate ? $user->savedJobs()->where('job_id', $job->getKey())->first() : null;
        $application = $isCandidate ? $user->jobApplications()->where('job_id', $job->getKey())->first() : null;

        $relatedQuery = Job::query()
            ->publiclyAvailable()
            ->where($job->getKeyName(), '!=', $job->getKey())
            ->where(function ($query) use ($job): void {
                if ($job->job_category_id) {
                    $query->orWhere('job_category_id', $job->job_category_id);
                }
                if ($job->industry_id) {
                    $query->orWhere('industry_id', $job->industry_id);
                }
                if ($job->location_id) {
                    $query->orWhere('location_id', $job->location_id);
                }
            })
            ->with(['company.location', 'category', 'location', 'skills'])
            ->latest('published_at')
            ->limit(3);
        $relatedJobs = $relatedQuery->get();

        $salary = null;
        if ($job->salary_min || $job->salary_max) {
            $amounts = collect([$job->salary_min, $job->salary_max])
                ->filter(fn ($amount): bool => $amount !== null)
                ->map(fn ($amount): string => '₹'.number_format((int) $amount));
            $salary = $amounts->join(' – ').($job->salary_period ? ' / '.Str::headline($job->salary_period) : '');
        }

        $jobLocation = $job->location ?? $job->company?->location;
        $structuredData = $job->company?->name && $jobLocation ? array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job->title,
            'description' => strip_tags($job->description),
            'datePosted' => $job->published_at?->toIso8601String(),
            'validThrough' => $job->application_deadline?->endOfDay()->toIso8601String(),
            'employmentType' => [match ($job->employment_type) {
                'full_time' => 'FULL_TIME',
                'part_time' => 'PART_TIME',
                'contract' => 'CONTRACTOR',
                'temporary' => 'TEMPORARY',
                'internship' => 'INTERN',
                default => 'OTHER',
            }],
            'directApply' => true,
            'hiringOrganization' => array_filter([
                '@type' => 'Organization',
                'name' => $job->company->name,
                'sameAs' => $job->company->website,
                'logo' => $job->company->logo_path ? Storage::disk('public')->url($job->company->logo_path) : null,
            ]),
            'jobLocation' => [
                '@type' => 'Place',
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'addressLocality' => $jobLocation->name,
                    'addressRegion' => $jobLocation->state,
                    'addressCountry' => 'IN',
                ]),
            ],
        ], fn ($value): bool => $value !== null) : null;

        if ($structuredData !== null && ($job->salary_min || $job->salary_max)) {
            $structuredData['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'currency' => $job->salary_currency,
                'value' => array_filter([
                    '@type' => 'QuantitativeValue',
                    'minValue' => $job->salary_min,
                    'maxValue' => $job->salary_max,
                    'unitText' => match ($job->salary_period) {
                        'hourly' => 'HOUR',
                        'monthly' => 'MONTH',
                        'weekly' => 'WEEK',
                        default => 'YEAR',
                    },
                ], fn ($value): bool => $value !== null),
            ];
        }

        $companyJobsCount = $job->company?->jobs()->publiclyAvailable()->count() ?? 0;

        return view('jobs.show', compact('job', 'savedJob', 'application', 'isCandidate', 'relatedJobs', 'salary', 'companyJobsCount', 'structuredData'));
    }
}
