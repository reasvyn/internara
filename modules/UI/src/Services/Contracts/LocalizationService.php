<?php

declare(strict_types=1);

namespace Modules\UI\Services\Contracts;

/**
 * Defines the contract for application localization management.
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
