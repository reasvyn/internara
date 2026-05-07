<?php

declare(strict_types=1);

namespace App\Entities\LogbookEntry;

use App\Entities\BaseEntity;
use App\Enums\Logbook\LogbookEntryStatus;
use Illuminate\Database\Eloquent\Model;

final readonly class LogbookEntryState extends BaseEntity
{
    public function __construct(
        private LogbookEntryStatus $status,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
        );
    }

    public function isVerified(): bool
    {
        return $this->status === LogbookEntryStatus::VERIFIED;
    }

    public function canBeEdited(): bool
    {
        return in_array(
            $this->status,
            [LogbookEntryStatus::DRAFT, LogbookEntryStatus::REVISION_REQUIRED],
            true,
        );
    }
}
