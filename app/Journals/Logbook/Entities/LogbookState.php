<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Entities;

use App\Core\Entities\BaseEntity;
use App\Journals\Logbook\Enums\LogbookStatus;
use Illuminate\Database\Eloquent\Model;

final readonly class LogbookState extends BaseEntity
{
    public function __construct(private LogbookStatus $status) {}

    public static function fromModel(Model $model): static
    {
        return new self(status: $model->status);
    }

    public function isVerified(): bool
    {
        return $this->status === LogbookStatus::VERIFIED;
    }

    public function canBeEdited(): bool
    {
        return in_array(
            $this->status,
            [LogbookStatus::DRAFT, LogbookStatus::REVISION_REQUIRED],
            true,
        );
    }
}
