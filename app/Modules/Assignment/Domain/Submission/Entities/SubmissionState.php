<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Domain\Submission\Entities;

use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class SubmissionState extends BaseEntity
{
    public function __construct(private SubmissionStatus $status) {}

    public static function fromModel(Model $model): static
    {
        return new self(status: $model->status);
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
