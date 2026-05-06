<?php

declare(strict_types=1);

namespace App\Domain\Log\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Activity log model extending Spatie's Activity with domain-specific scopes.
 *
 * Provides operational activity tracking for model changes, user actions,
 * and system events across all domains.
 */
class ActivityLog extends Activity
{
    public function scopeForUser(Builder $query, string|int $userId): Builder
    {
        return $query->where('causer_id', $userId);
    }

    public function scopeForSubject(Builder $query, string $type, string|int|null $id = null): Builder
    {
        $query->where('subject_type', $type);

        if ($id !== null) {
            $query->where('subject_id', $id);
        }

        return $query;
    }

    public function scopeOfAction(Builder $query, string $action): Builder
    {
        return $query->where('event', $action);
    }

    public function scopeInLog(Builder $query, string|array $names): Builder
    {
        $names = is_array($names) ? $names : func_get_args();

        return $query->whereIn('log_name', $names);
    }

    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->latest()->limit($limit);
    }

    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where(function (Builder $q) use ($module) {
            $q->where('subject_type', 'like', "%\\{$module}\\%")
                ->orWhere('log_name', $module);
        });
    }

    public function scopeGroupedByDay(Builder $query, int $days = 30): Collection
    {
        return $query->lastDays($days)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function getSubjectModelAttribute(): ?string
    {
        if (! $this->subject_type) {
            return null;
        }

        $parts = explode('\\', $this->subject_type);

        return end($parts);
    }
}
