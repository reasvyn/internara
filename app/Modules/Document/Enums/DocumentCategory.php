<?php

declare(strict_types=1);

namespace App\Modules\Document\Enums;

use App\Modules\Core\Contracts\LabelEnum;

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
    case HANDBOOK = 'handbook';

    public function label(): string
    {
        return match ($this) {
            self::APPLICATION => __('common.enums.application'),
            self::PERMIT => __('common.enums.permit'),
            self::CERTIFICATE => __('common.enums.certificate'),
            self::REPORT => __('common.enums.report'),
            self::LETTER => __('common.enums.letter'),
            self::POLICY => __('common.enums.policy'),
            self::HANDBOOK => __('common.enums.handbook'),
        };
    }
}
