<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidatePortfolio extends Model
{
    protected $fillable = ['title', 'description', 'url', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'date'];
    }

    /** @return BelongsTo<CandidateProfile, $this> */
    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }
}
