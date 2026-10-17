<?php

namespace App\Models;

use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $blog_author_slug
 * @property string|null $blog_display_name
 * @property string|null $blog_bio
 * @property string|null $blog_profile_image_path
 * @property bool $is_suspended
 * @property Carbon|null $suspended_at
 * @property int|null $suspended_by_user_id
 * @property string|null $suspension_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->notifications()->delete();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_suspended' => 'boolean',
            'suspended_at' => 'immutable_datetime',
        ];
    }

    /**
     * Determine if the user is an owner.
     */
    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    /**
     * Determine if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Determine if the user is a candidate.
     */
    public function isCandidate(): bool
    {
        return $this->role === UserRole::Candidate;
    }

    /**
     * Determine if the user is an employer.
     */
    public function isEmployer(): bool
    {
        return $this->role === UserRole::Employer;
    }

    /**
     * Determine whether the user has one of the supplied roles.
     */
    public function hasAnyRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_suspended', false);
    }

    public function isSuspended(): bool
    {
        return (bool) $this->is_suspended;
    }

    /** @return BelongsTo<User, $this> */
    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by_user_id');
    }

    /**
     * The profile belonging to a candidate account.
     *
     * @return HasOne<CandidateProfile, $this>
     */
    public function candidateProfile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class);
    }

    /** @return HasMany<Company, $this> */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /** @return HasMany<SavedJob, $this> */
    public function savedJobs(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }

    /** @return HasMany<JobApplication, $this> */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** @return HasMany<JobAlert, $this> */
    public function jobAlerts(): HasMany
    {
        return $this->hasMany(JobAlert::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function candidateConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'candidate_user_id');
    }

    /** @return HasMany<Conversation, $this> */
    public function employerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'employer_user_id');
    }

    /** @return HasMany<Message, $this> */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_user_id');
    }

    /** @return HasMany<Meeting, $this> */
    public function candidateMeetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'candidate_user_id');
    }

    /** @return HasMany<Meeting, $this> */
    public function employerMeetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'employer_user_id');
    }

    /** @return HasMany<CompanyReview, $this> */
    public function companyReviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class);
    }

    /** @return HasMany<Article, $this> */
    public function blogArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    /** @return BelongsToMany<Company, $this> */
    public function followedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_follows')->withTimestamps();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function blogAuthorName(): string
    {
        return trim((string) $this->blog_display_name) !== ''
            ? (string) $this->blog_display_name
            : $this->name;
    }

    public function blogAuthorSlug(): string
    {
        return $this->blog_author_slug ?: Str::slug($this->blogAuthorName()).'-'.$this->getKey();
    }

    public function blogProfileImageUrl(): ?string
    {
        if (! $this->blog_profile_image_path || ! Storage::disk('public')->exists($this->blog_profile_image_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->blog_profile_image_path);
    }
}
