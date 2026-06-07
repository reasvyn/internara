<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Enums;

use App\Core\Contracts\LabelEnum;
use App\Core\Contracts\StatusEnum;

enum LogbookStatus: string implements LabelEnum, StatusEnum
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case REVISION_REQUIRED = 'revision_required';

    public function isFinalized(): bool
    {
        return $this === self::VERIFIED;
    }

    public function requiresAction(): bool
    {
        return in_array($this, [self::SUBMITTED, self::REVISION_REQUIRED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::SUBMITTED => __('Submitted'),
            self::VERIFIED => __('Verified'),
            self::REVISION_REQUIRED => __('Revision Required'),
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::VERIFIED;
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SUBMITTED],
            self::SUBMITTED => [self::VERIFIED, self::REVISION_REQUIRED],
            self::REVISION_REQUIRED => [self::DRAFT],
            self::VERIFIED => [],
        };
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        if (! ($target instanceof self)) {
            return false;
        }

        return in_array($target, $this->validTransitions(), true);
    }
}
