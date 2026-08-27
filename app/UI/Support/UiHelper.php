<?php

declare(strict_types=1);

namespace App\UI\Support;

/**
 * Presentation helpers for the UI module.
 *
 * UI is a pure Blade-component module (layouts, ui/*, widgets).
 * No business logic — only view helpers and Tailwind token access.
 * Depends on Core only via contracts.
 */
final class UiHelper
{
    /**
     * Get the Tailwind semantic color tokens available in this app.
     *
     * @return list<string>
     */
    public static function semanticColors(): array
    {
        return [
            'primary',
            'secondary',
            'accent',
            'neutral',
            'base-100',
            'base-200',
            'base-300',
            'info',
            'success',
            'warning',
            'error',
        ];
    }

    /**
     * Check if a Blade view belongs to the UI module.
     */
    public static function isUiView(string $view): bool
    {
        return str_starts_with($view, 'ui::');
    }
}
