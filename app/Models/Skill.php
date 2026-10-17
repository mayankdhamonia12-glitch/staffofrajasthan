<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsToMany<CandidateProfile, $this> */
    public function candidateProfiles(): BelongsToMany
    {
        return $this->belongsToMany(CandidateProfile::class)->withTimestamps();
    }
}
