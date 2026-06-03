<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Aggregates\Submission\Entities;

use App\Domain\Assignment\Aggregates\Submission\Enums\SubmissionStatus;
use App\Domain\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class SubmissionState extends BaseEntity
{
    public function __construct(
        private SubmissionStatus $status,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
        );
    }

    public function canBeEdited(): bool
    {
        return in_array(
            $this->status,
            [SubmissionStatus::DRAFT, SubmissionStatus::REVISION_REQUIRED],
            true,
        );
    }

    public function isVerified(): bool
    {
        return $this->status === SubmissionStatus::VERIFIED;
    }
}
