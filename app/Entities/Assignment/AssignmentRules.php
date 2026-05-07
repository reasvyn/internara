<?php

declare(strict_types=1);

namespace App\Entities\Assignment;

use App\Entities\BaseEntity;
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

    public function isOverdue(): bool
    {
        return $this->dueDate && Carbon::now()->greaterThan($this->dueDate);
    }
}
