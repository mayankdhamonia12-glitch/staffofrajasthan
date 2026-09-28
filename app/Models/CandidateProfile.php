<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'headline', 'phone', 'location', 'about', 'resume_path', 'linkedin_url', 'portfolio_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withTimestamps();
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class)->latest('end_date');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class)->latest('start_date');
    }
}
