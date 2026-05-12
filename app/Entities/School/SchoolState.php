<?php

declare(strict_types=1);

namespace App\Entities\School;

use App\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class SchoolState extends BaseEntity
{
    public function __construct(
        private bool $singleRecordEnabled,
        private bool $exists,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            singleRecordEnabled: (bool) config('school.single_record', true),
            exists: $model->getAttribute('id') !== null,
        );
    }

    public function canBeCreated(): bool
    {
        return $this->singleRecordEnabled ? ! $this->exists : true;
    }
}
