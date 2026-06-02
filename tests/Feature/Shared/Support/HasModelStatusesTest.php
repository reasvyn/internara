<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Core\Models\BaseModel;
use App\Domain\Shared\Support\HasModelStatuses;
use Illuminate\Support\Facades\Schema;

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
            TestStatus::DRAFT => [TestStatus::PUBLISHED, TestStatus::ARCHIVED],
            TestStatus::PUBLISHED => [TestStatus::DRAFT, TestStatus::ARCHIVED],
            TestStatus::ARCHIVED => [],
        };
    }
}

class TestStatusModel extends BaseModel
{
    use HasModelStatuses;

    protected $table = 'test_status_models';

    protected $fillable = ['status'];

    protected function statusEnumClass(): string
    {
        return TestStatus::class;
    }

    public function getStatusAttribute(): mixed
    {
        $status = $this->attributes['status'] ?? null;

        return $status !== null ? (object) ['name' => $status] : null;
    }

    public function setStatus(string $name, ?string $reason = null): static
    {
        $this->attributes['status'] = $name;

        return $this;
    }

    public function hasStatus(string $name): bool
    {
        $status = $this->getStatusAttribute();

        return $status?->name === $name;
    }
}

describe('HasModelStatuses', function () {
    beforeEach(function () {
        if (! Schema::hasTable('test_status_models')) {
            Schema::create('test_status_models', function ($table) {
                $table->uuid('id')->primary();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }
    });

    it('sets status enum value', function () {
        $model = TestStatusModel::create(['status' => null]);

        $model->setStatusEnum(TestStatus::PUBLISHED);

        expect($model->status?->name)->toBe('published');
    });

    it('checks if model has a specific status', function () {
        $model = TestStatusModel::create(['status' => 'draft']);

        expect($model->hasStatusEnum(TestStatus::DRAFT))->toBeTrue();
        expect($model->hasStatusEnum(TestStatus::PUBLISHED))->toBeFalse();
    });

    it('returns current status as enum instance', function () {
        $model = TestStatusModel::create(['status' => 'published']);

        $status = $model->currentStatus();

        expect($status)->toBeInstanceOf(TestStatus::class);
        expect($status)->toBe(TestStatus::PUBLISHED);
    });

    it('returns null when status is not set', function () {
        $model = TestStatusModel::create(['status' => null]);

        expect($model->currentStatus())->toBeNull();
    });
});
