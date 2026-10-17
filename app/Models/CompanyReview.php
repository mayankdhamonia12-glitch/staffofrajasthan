<?php

namespace App\Models;

use App\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $rating
 * @property ReviewStatus $status
 */
class CompanyReview extends Model
{
    protected $fillable = ['company_id', 'user_id', 'rating', 'title', 'body', 'status', 'moderated_by_user_id', 'moderated_at', 'moderation_note'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'status' => ReviewStatus::class, 'moderated_at' => 'immutable_datetime'];
    }

    /**
     * @param  Builder<CompanyReview>  $query
     * @return Builder<CompanyReview>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Approved->value);
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by_user_id');
    }
}
