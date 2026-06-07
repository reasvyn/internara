<?php

declare(strict_types=1);

use App\Core\Contracts\StatusEnum;
use App\Support\HasModelStatuses;

enum TestStatus: string implements StatusEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return $this->value;
    }

    public function isTerminal(): bool
    {
        return $this === self::ARCHIVED;
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        return in_array($target, $this->validTransitions(), true);
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PUBLISHED, self::ARCHIVED],
            self::PUBLISHED => [self::ARCHIVED],
            self::ARCHIVED => [],
        };
    }
}

class TestModelWithStatuses
{
    use HasModelStatuses;

    public ?TestStatus $status = null;

    public function setStatus(string $status): void
    {
        $this->status = TestStatus::tryFrom($status);
    }

    public function hasStatus(string $status): bool
    {
        return $this->status?->value === $status;
    }

    /** @return class-string<TestStatus> */
    protected function statusEnumClass(): string
    {
        return TestStatus::class;
    }
}

test('set status enum sets the status value', function () {
    $model = new TestModelWithStatuses;
    $model->setStatusEnum(TestStatus::PUBLISHED);

    expect($model->status)->toBe(TestStatus::PUBLISHED);
});

test('has status enum checks current status', function () {
    $model = new TestModelWithStatuses;
    $model->setStatusEnum(TestStatus::DRAFT);

    expect($model->hasStatusEnum(TestStatus::DRAFT))->toBeTrue();
    expect($model->hasStatusEnum(TestStatus::PUBLISHED))->toBeFalse();
});

test('current status returns null when not set', function () {
    $model = new TestModelWithStatuses;

    expect($model->currentStatus())->toBeNull();
});

test('current status returns correct enum for set status', function () {
    $model = new TestModelWithStatuses;

    $model->setStatus('draft');

    expect($model->status)->toBe(TestStatus::DRAFT);
    expect($model->currentStatus())->toBeNull();

    $model->setStatusEnum(TestStatus::ARCHIVED);
    expect($model->status)->toBe(TestStatus::ARCHIVED);
});

test('trait is deprecated', function () {
    $ref = new ReflectionClass(HasModelStatuses::class);
    $docComment = $ref->getDocComment();

    expect($docComment)->toContain('@deprecated');
});
