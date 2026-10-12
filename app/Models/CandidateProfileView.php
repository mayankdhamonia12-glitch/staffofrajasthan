<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfileView extends Model
{
    public $timestamps = false;

    protected $fillable = ['candidate_profile_id', 'visitor_fingerprint', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    /** @return BelongsTo<CandidateProfile, $this> */
    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }
}
