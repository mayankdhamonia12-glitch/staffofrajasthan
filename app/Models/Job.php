<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Job extends Model
{
    protected $fillable = ['job_category_id', 'industry_id', 'location_id', 'title', 'slug', 'description', 'responsibilities', 'requirements', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'salary_currency', 'salary_period', 'vacancies', 'application_deadline', 'status', 'is_featured', 'is_urgent', 'is_filled', 'published_at'];

    protected function casts(): array
    {
        return ['application_deadline' => 'date', 'published_at' => 'datetime', 'is_featured' => 'boolean', 'is_urgent' => 'boolean', 'is_filled' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }
}
