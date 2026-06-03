<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Aggregates\Submission\Enums;

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;

enum SubmissionStatus: string implements LabelEnum, StatusEnum
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case GRADED = 'graded';
    case REVISION_REQUIRED = 'revision_required';

    public function isFinalized(): bool
    {
        return in_array($this, [self::VERIFIED, self::GRADED], true);
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
            self::GRADED => __('Graded'),
            self::REVISION_REQUIRED => __('Revision Required'),
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::VERIFIED, self::GRADED], true);
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SUBMITTED],
            self::SUBMITTED => [self::VERIFIED, self::GRADED, self::REVISION_REQUIRED],
            self::REVISION_REQUIRED => [self::DRAFT],
            self::VERIFIED => [],
            self::GRADED => [],
        };
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        if (! $target instanceof self) {
            return false;
        }

        return in_array($target, $this->validTransitions(), true);
    }
}
