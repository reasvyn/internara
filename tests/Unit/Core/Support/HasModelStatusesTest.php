<?php

declare(strict_types=1);

use App\Core\Contracts\StatusEnum;
use App\Core\Support\HasModelStatuses;
use Illuminate\Database\Eloquent\Model;

enum TestStatusEnum: string implements StatusEnum
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
        return true;
    }

    public function validTransitions(): array
    {
        return [self::PUBLISHED, self::ARCHIVED];
    }
}

class TestModelWithStatuses extends Model
{
    use HasModelStatuses;

    protected $guarded = [];

    protected $casts = [
        'status' => TestStatusEnum::class,
    ];

    public function setStatus(string $value): void
    {
        $this->status = $value;
    }

    public function hasStatus(string $value): bool
    {
        return $this->status?->value === $value;
    }

    protected function statusEnumClass(): string
    {
        return TestStatusEnum::class;
    }
}

beforeEach(function () {
    $this->model = new TestModelWithStatuses;
    $this->model->status = TestStatusEnum::DRAFT;
});

test('setStatusEnum sets status value from enum', function () {
    $this->model->setStatusEnum(TestStatusEnum::PUBLISHED);

    expect($this->model->status)->toBe(TestStatusEnum::PUBLISHED);
});

test('setStatusEnum returns self for chaining', function () {
    $result = $this->model->setStatusEnum(TestStatusEnum::PUBLISHED);

    expect($result)->toBe($this->model);
});

test('hasStatusEnum returns true when status matches', function () {
    expect($this->model->hasStatusEnum(TestStatusEnum::DRAFT))->toBeTrue();
});

test('hasStatusEnum returns false when status does not match', function () {
    expect($this->model->hasStatusEnum(TestStatusEnum::PUBLISHED))->toBeFalse();
});

test('currentStatus returns null due to tryFrom using name instead of value (deprecated trait limitation)', function () {
    $status = $this->model->currentStatus();

    expect($status)->toBeNull();
});
