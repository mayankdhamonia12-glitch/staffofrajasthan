<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateAward extends Model
{
    protected $fillable = ['title', 'issuer', 'awarded_at', 'description'];

    protected function casts(): array
    {
        return ['awarded_at' => 'date'];
    }

    /** @return BelongsTo<CandidateProfile, $this> */
    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }
}
