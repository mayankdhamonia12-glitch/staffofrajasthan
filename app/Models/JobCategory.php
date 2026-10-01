<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<Job, $this> */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /** @return BelongsToMany<CandidateProfile, $this> */
    public function candidateProfiles(): BelongsToMany
    {
        return $this->belongsToMany(CandidateProfile::class, 'candidate_profile_job_category');
    }
}
