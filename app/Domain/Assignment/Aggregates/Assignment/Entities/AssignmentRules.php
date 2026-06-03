<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Aggregates\Assignment\Entities;

use App\Domain\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class AssignmentRules extends BaseEntity
{
    public function __construct(
        private bool $isMandatory,
        private ?Carbon $dueDate,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            isMandatory: $model->is_mandatory === true,
            dueDate: $model->due_date,
        );
    }

    public function isMandatory(): bool
    {
        return $this->isMandatory;
    }

    public function isOverdue(Carbon $now): bool
    {
        return $this->dueDate && $now->greaterThan($this->dueDate);
    }
}
