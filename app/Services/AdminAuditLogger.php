<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        User $actor,
        string $action,
        ?Model $subject,
        string $summary,
        ?string $reason = null,
        ?array $before = null,
        ?array $after = null,
    ): AdminAuditLog {
        return AdminAuditLog::query()->create([
            'actor_user_id' => $actor->getKey(),
            'action' => Str::limit(trim($action), 100, ''),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'summary' => Str::limit(trim($summary), 500, ''),
            'reason' => $reason === null ? null : Str::limit(trim($reason), 500, ''),
            'before_values' => $this->safeValues($before),
            'after_values' => $this->safeValues($after),
            'created_at' => now(),
        ]);
    }

    /** @param array<string, mixed>|null $values
     * @return array<string, mixed>|null
     */
    private function safeValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $blocked = ['password', 'password_hash', 'token', 'secret', 'api_key', 'session_id', 'body', 'cover_letter', 'resume', 'resume_path', 'message'];
        $safe = [];

        foreach ($values as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, $blocked, true)) {
                continue;
            }

            $safe[$key] = is_array($value) ? $this->safeValues($value) : $value;
        }

        return $safe;
    }
}
