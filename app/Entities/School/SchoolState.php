<?php

declare(strict_types=1);

namespace App\Entities\School;

use App\Entities\BaseEntity;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;

final readonly class SchoolState extends BaseEntity
{
    public function __construct(
        private bool $singleRecordEnabled,
        private bool $exists,
    ) {}

    public static function fromModel(Model $model): static
    {
        assert($model instanceof School);

        return new self(
            singleRecordEnabled: $model->schoolSingleRecordEnabled(),
            exists: $model->schoolRecordExists(),
        );
    }

    /**
     * Check if a new school record can be created.
     */
    public function canBeCreated(): bool
    {
        return $this->singleRecordEnabled ? ! $this->exists : true;
    }
}
