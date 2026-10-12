<?php

namespace App\Models;

use App\Services\CandidateProfileCompletionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CandidateProfile extends Model
{
    protected $fillable = [
        'slug', 'headline', 'phone', 'location', 'about', 'resume_path', 'resume_title',
        'resume_original_name', 'resume_mime_type', 'resume_size', 'resume_uploaded_at',
        'profile_photo_path', 'date_of_birth', 'gender', 'experience_level', 'years_experience',
        'months_experience', 'expected_salary', 'salary_type', 'city', 'state', 'country',
        'address', 'latitude', 'longitude', 'linkedin_url', 'facebook_url', 'twitter_url',
        'website_url', 'portfolio_url', 'video_provider', 'intro_video_url', 'intro_video_title',
        'availability_status', 'is_public', 'show_expected_salary', 'preferred_employment_types',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'resume_uploaded_at' => 'datetime',
            'is_public' => 'boolean',
            'show_expected_salary' => 'boolean',
            'preferred_employment_types' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('proficiency')->withTimestamps();
    }

    /** @return HasMany<Education, $this> */
    public function educations(): HasMany
    {
        return $this->hasMany(Education::class)->latest('end_date');
    }

    /** @return HasMany<Experience, $this> */
    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class)->latest('start_date');
    }

    /** @return BelongsToMany<JobCategory, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(JobCategory::class, 'candidate_profile_job_category');
    }

    /** @return HasMany<CandidateLanguage, $this> */
    public function languages(): HasMany
    {
        return $this->hasMany(CandidateLanguage::class)->orderBy('language');
    }

    /** @return HasMany<CandidatePortfolio, $this> */
    public function portfolios(): HasMany
    {
        return $this->hasMany(CandidatePortfolio::class)->latest('completed_at');
    }

    /** @return HasMany<CandidateAward, $this> */
    public function awards(): HasMany
    {
        return $this->hasMany(CandidateAward::class)->latest('awarded_at');
    }

    /** @return HasMany<CandidateShortlist, $this> */
    public function shortlists(): HasMany
    {
        return $this->hasMany(CandidateShortlist::class);
    }

    /** @return HasMany<CandidateProfileView, $this> */
    public function profileViews(): HasMany
    {
        return $this->hasMany(CandidateProfileView::class);
    }

    public function completionPercentage(): int
    {
        return app(CandidateProfileCompletionService::class)->calculate($this, $this->user->name)['percentage'];
    }

    public function publicSlug(string $name): string
    {
        return $this->slug ?: Str::slug($name.'-'.$this->getKey());
    }
}
