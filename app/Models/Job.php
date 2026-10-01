<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable|null $application_deadline
 * @property CarbonImmutable|null $published_at
 */
class Job extends Model
{
    protected $fillable = ['job_category_id', 'industry_id', 'location_id', 'title', 'slug', 'description', 'responsibilities', 'requirements', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'salary_currency', 'salary_period', 'vacancies', 'application_deadline', 'status', 'is_featured', 'is_urgent', 'is_filled', 'published_at'];

    protected function casts(): array
    {
        return ['application_deadline' => 'date', 'published_at' => 'datetime', 'is_featured' => 'boolean', 'is_urgent' => 'boolean', 'is_filled' => 'boolean'];
    }

    /**
     * @param  Builder<Job>  $query
     * @return Builder<Job>
     */
    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('is_filled', false)
            ->where(fn (Builder $jobs) => $jobs->whereNull('application_deadline')->orWhereDate('application_deadline', '>=', today()));
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<JobCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    /** @return BelongsTo<Industry, $this> */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    /** @return HasMany<SavedJob, $this> */
    public function savedBy(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }

    /** @return HasMany<JobApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
