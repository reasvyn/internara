<?php

declare(strict_types=1);

namespace App\Entities\Internship;

use App\Entities\BaseEntity;
use App\Enums\Internship\InternshipStatus;
use Illuminate\Database\Eloquent\Model;

final readonly class InternshipPeriod extends BaseEntity
{
    public function __construct(
        private ?InternshipStatus $status,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
        );
    }

    public function isAcceptingRegistrations(): bool
    {
        return $this->status?->isAcceptingRegistrations() ?? false;
    }
}
