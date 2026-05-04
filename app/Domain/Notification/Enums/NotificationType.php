<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Notification types for categorization.
 */
enum NotificationType: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
    case SYSTEM = 'system';

    public function icon(): string
    {
        return match ($this) {
            self::SUCCESS => 'o-check-circle',
            self::ERROR => 'o-x-circle',
            self::WARNING => 'o-exclamation-triangle',
            self::INFO => 'o-information-circle',
            self::SYSTEM => 'o-cog',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUCCESS => 'success',
            self::ERROR => 'error',
            self::WARNING => 'warning',
            self::INFO => 'info',
            self::SYSTEM => 'neutral',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'Success',
            self::ERROR => 'Error',
            self::WARNING => 'Warning',
            self::INFO => 'Information',
            self::SYSTEM => 'System',
        };
    }
}
