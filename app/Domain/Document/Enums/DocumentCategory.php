<?php

declare(strict_types=1);

namespace App\Domain\Document\Enums;

use App\Domain\Core\Contracts\LabelEnum;

/**
 * Categories for document templates.
 */
enum DocumentCategory: string implements LabelEnum
{
    case APPLICATION = 'application';
    case PERMIT = 'permit';
    case CERTIFICATE = 'certificate';
    case REPORT = 'report';
    case LETTER = 'letter';

    public function label(): string
    {
        return match ($this) {
            self::APPLICATION => __('Application'),
            self::PERMIT => __('Permit'),
            self::CERTIFICATE => __('Certificate'),
            self::REPORT => __('Report'),
            self::LETTER => __('Letter'),
        };
    }
}
