<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Entities;

use App\Modules\Core\Entities\BaseEntity;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class RegistrationState extends BaseEntity
{
    private ?RegistrationStatus $statusEnum;

    public function __construct(
        private RegistrationStatus|string|null $status,
        private Carbon|string|null $startDate,
        private Carbon|string|null $endDate,
        private bool $hasPlacement,
        private array $phases = [],
    ) {
        $this->statusEnum = $this->status instanceof RegistrationStatus
            ? $this->status
            : ($this->status !== null ? RegistrationStatus::tryFrom($this->status) : null);
    }

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

    public function status(): ?RegistrationStatus
    {
        return $this->statusEnum;
    }

    public function rawStatus(): ?string
    {
        return $this->statusEnum?->value ?? ($this->status instanceof RegistrationStatus ? $this->status->value : $this->status);
    }

    public function isPreparation(): bool
    {
        return $this->statusEnum === RegistrationStatus::PREPARATION;
    }

    public function isActive(): bool
    {
        return $this->statusEnum === RegistrationStatus::ACTIVE || $this->rawStatus() === 'active';
    }

    public function isPending(): bool
    {
        return $this->statusEnum === RegistrationStatus::PENDING || $this->rawStatus() === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->statusEnum === RegistrationStatus::CONFIRMED;
    }

    public function isCompleted(): bool
    {
        return $this->statusEnum === RegistrationStatus::COMPLETED;
    }

    public function isRejected(): bool
    {
        return $this->statusEnum === RegistrationStatus::REJECTED;
    }

    public function canTransitionTo(RegistrationStatus $target): bool
    {
        if ($this->statusEnum === null) {
            return false;
        }

        return $this->statusEnum->canTransitionTo($target);
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
