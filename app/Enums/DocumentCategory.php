<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Categories for document templates.
 */
enum DocumentCategory: string
{
    case APPLICATION = 'application';
    case PERMIT = 'permit';
    case CERTIFICATE = 'certificate';
    case REPORT = 'report';
    case LETTER = 'letter';

    public function label(): string
    {
        return match ($this) {
            self::APPLICATION => 'Application',
            self::PERMIT => 'Permit',
            self::CERTIFICATE => 'Certificate',
            self::REPORT => 'Report',
            self::LETTER => 'Letter',
        };
    }
}
