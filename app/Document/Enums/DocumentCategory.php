<?php

declare(strict_types=1);

namespace App\Document\Enums;

use App\Core\Contracts\LabelEnum;

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
    case POLICY = 'policy';

    public function label(): string
    {
        return match ($this) {
            self::APPLICATION => __('Application'),
            self::PERMIT => __('Permit'),
            self::CERTIFICATE => __('Certificate'),
            self::REPORT => __('Report'),
            self::LETTER => __('Letter'),
            self::POLICY => __('Policy'),
        };
    }
}
