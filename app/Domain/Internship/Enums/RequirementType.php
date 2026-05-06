<?php

declare(strict_types=1);

namespace App\Domain\Internship\Enums;

use App\Domain\Shared\Contracts\LabelEnum;

/**
 * Types of internship requirements.
 */
enum RequirementType: string implements LabelEnum
{
    case DOCUMENT = 'document';
    case SKILL = 'skill';
    case TEXT = 'text';

    public function supportsFileUpload(): bool
    {
        return $this === self::DOCUMENT;
    }

    public function label(): string
    {
        return match ($this) {
            self::DOCUMENT => 'Document',
            self::SKILL => 'Skill',
            self::TEXT => 'Text',
        };
    }
}
