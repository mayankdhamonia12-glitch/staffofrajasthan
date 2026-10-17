<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'industry_id', 'location_id', 'name', 'slug', 'email', 'phone', 'website',
        'linkedin_url', 'facebook_url', 'twitter_url', 'instagram_url',
        'logo_path', 'cover_path', 'description', 'address', 'company_size',
        'founded_year', 'is_visible',
    ];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'founded_year' => 'integer'];
    }

    /**
     * @param  Builder<Company>  $query
     * @return Builder<Company>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)
            ->whereHas('user', fn (Builder $users) => $users->where('is_suspended', false));
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /** @return HasMany<Job, $this> */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /** @return BelongsToMany<CandidateProfile, $this> */
    public function shortlistedCandidates(): BelongsToMany
    {
        return $this->belongsToMany(CandidateProfile::class, 'candidate_shortlists')->withTimestamps();
    }

    /** @return BelongsToMany<User, $this> */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_follows')->withTimestamps();
    }

    /** @return HasMany<CompanyReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class);
    }
}
