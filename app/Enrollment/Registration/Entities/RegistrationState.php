<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Entities;

use App\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class RegistrationState extends BaseEntity
{
    public function __construct(
        private ?string $status,
        private Carbon|string|null $startDate,
        private Carbon|string|null $endDate,
        private bool $hasPlacement,
        private array $phases = [],
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->getAttribute('status'),
            startDate: $model->start_date,
            endDate: $model->end_date,
            hasPlacement: $model->placement_id !== null,
        );
    }

    private function startCarbon(): ?Carbon
    {
        return $this->startDate instanceof Carbon ? $this->startDate : ($this->startDate ? Carbon::parse($this->startDate) : null);
    }

    private function endCarbon(): ?Carbon
    {
        return $this->endDate instanceof Carbon ? $this->endDate : ($this->endDate ? Carbon::parse($this->endDate) : null);
    }

    public function withPhases(array $phases): static
    {
        return $this->with('phases', $phases);
    }

    public function isActive(): bool
    {
        // @todo Replace with enum value when RegistrationStatus enum exists
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        // @todo Replace with enum value when RegistrationStatus enum exists
        return $this->status === 'pending';
    }

    public function isCurrentlyOngoing(?Carbon $today = null): bool
    {
        $today ??= new Carbon;
        $start = $this->startCarbon();
        $end = $this->endCarbon();

        if (! $start || ! $end) {
            return false;
        }

        return $today->between($start, $end, true);
    }

    public function hasEnded(?Carbon $today = null): bool
    {
        $today ??= new Carbon;
        $end = $this->endCarbon();

        if (! $end) {
            return false;
        }

        return $today->isAfter($end);
    }

    public function canBeApproved(): bool
    {
        return $this->isPending() && $this->hasPlacement;
    }

    public function daysRemaining(?Carbon $today = null): int
    {
        $today ??= new Carbon;
        $end = $this->endCarbon();

        if (! $end) {
            return 0;
        }

        return max(0, (int) $today->diffInDays($end, false));
    }

    public function totalDuration(): int
    {
        $start = $this->startCarbon();
        $end = $this->endCarbon();

        if (! $start || ! $end) {
            return 0;
        }

        return (int) $start->diffInDays($end);
    }

    /**
     * @return array<int, array{name: string, order: int, weight: int}>
     */
    public function phases(): array
    {
        return $this->phases;
    }

    public function currentPhaseIndex(?Carbon $now = null): ?int
    {
        $start = $this->startCarbon();
        $end = $this->endCarbon();

        if ($this->phases === [] || ! $start || ! $end) {
            return null;
        }

        $now ??= new Carbon;
        $totalDays = $start->diffInDays($end);

        if ($totalDays <= 0) {
            return null;
        }

        $elapsedDays = $start->diffInDays($now, false);
        $elapsedPercent = ($elapsedDays / $totalDays) * 100;

        if ($elapsedPercent <= 0) {
            return 0;
        }

        $cumulative = 0;
        foreach ($this->phases as $index => $phase) {
            $cumulative += $phase['weight'];
            if ($elapsedPercent <= $cumulative) {
                return $index;
            }
        }

        return count($this->phases) - 1;
    }

    public function currentPhase(?Carbon $now = null): ?string
    {
        $index = $this->currentPhaseIndex($now);

        if ($index === null) {
            return null;
        }

        return $this->phases[$index]['name'] ?? null;
    }
}
