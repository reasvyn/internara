<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Shared\Support\HasModelStatuses;

enum TestStatus: string implements StatusEnum
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return $this->value;
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        return true;
    }

    public function isTerminal(): bool
    {
        return $this === self::COMPLETED;
    }

    public function validTransitions(): array
    {
        return [];
    }
}

class TestStatusesModel
{
    use HasModelStatuses;

    public ?object $status = null;

    public function setStatus(string $name, ?string $reason = null): static
    {
        $this->status = (object) ['name' => $name];

        return $this;
    }

    public function hasStatus(string $name): bool
    {
        return $this->status?->name === $name;
    }

    protected function statusEnumClass(): string
    {
        return TestStatus::class;
    }
}

describe('HasModelStatuses', function () {
    it('sets status via enum', function () {
        $model = new TestStatusesModel;
        $model->setStatusEnum(TestStatus::ACTIVE);

        expect($model->status->name)->toBe('active');
    });

    it('checks if model has status enum', function () {
        $model = new TestStatusesModel;
        $model->setStatusEnum(TestStatus::ACTIVE);

        expect($model->hasStatusEnum(TestStatus::ACTIVE))->toBeTrue()
            ->and($model->hasStatusEnum(TestStatus::DRAFT))->toBeFalse();
    });

    it('returns current status as enum', function () {
        $model = new TestStatusesModel;
        $model->setStatusEnum(TestStatus::ACTIVE);

        $status = $model->currentStatus();

        expect($status)->toBe(TestStatus::ACTIVE);
    });

    it('returns null when no status is set', function () {
        $model = new TestStatusesModel;

        expect($model->currentStatus())->toBeNull();
    });

    it('returns null for unknown status name', function () {
        $model = new TestStatusesModel;
        $model->setStatus('unknown-status-name');

        expect($model->currentStatus())->toBeNull();
    });
});
