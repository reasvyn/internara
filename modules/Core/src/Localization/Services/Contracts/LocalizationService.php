<?php

declare(strict_types=1);

namespace Modules\Core\Localization\Services\Contracts;

/**
 * Defines the contract for application localization management within Core module.
 *
 * This interface provides minimal localization support without depending on
 * the UI module, maintaining the architectural mandate that Core only
 * depends on Shared module.
 */
interface LocalizationService
{
    /**
     * Get the supported locales.
     *
     * @return array<string, array{name: string, icon: string}>
     */
    public function getSupportedLocales(): array;

    /**
     * Change the active application locale.
     *
     * @param string $locale The locale key (e.g., 'id', 'en').
     */
    public function setLocale(string $locale): bool;

    /**
     * Get the current application locale.
     */
    public function getCurrentLocale(): string;
}
