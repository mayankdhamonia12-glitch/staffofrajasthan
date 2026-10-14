<?php

namespace App;

enum JobApplicationStatus: string
{
    case Applied = 'applied';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Selected = 'selected';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::Shortlisted => 'Shortlisted',
            self::Interview => 'Interview',
            self::Selected => 'Selected',
            self::Rejected => 'Rejected',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Applied => 'bg-blue-50 text-blue-800 ring-blue-600/15',
            self::Shortlisted => 'bg-amber-50 text-amber-800 ring-amber-600/15',
            self::Interview => 'bg-violet-50 text-violet-800 ring-violet-600/15',
            self::Selected => 'bg-emerald-50 text-emerald-800 ring-emerald-600/15',
            self::Rejected => 'bg-rose-50 text-rose-800 ring-rose-600/15',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    public static function labelFor(string $value): string
    {
        return match ($value) {
            'submitted' => self::Applied->label(),
            'reviewing' => self::Shortlisted->label(),
            'accepted' => self::Selected->label(),
            default => self::tryFrom($value)?->label() ?? str($value)->headline()->toString(),
        };
    }

    public static function toneFor(string $value): string
    {
        $normalized = match ($value) {
            'submitted' => self::Applied,
            'reviewing' => self::Shortlisted,
            'accepted' => self::Selected,
            default => self::tryFrom($value),
        };

        return $normalized?->tone() ?? 'bg-slate-100 text-slate-700 ring-slate-500/15';
    }
}
