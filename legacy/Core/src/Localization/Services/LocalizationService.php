<?php

declare(strict_types=1);

namespace Modules\Core\Localization\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Modules\Core\Localization\Services\Contracts\LocalizationService as Contract;

/**
 * Core module implementation of localization service.
 *
 * Provides locale management without depending on UI module,
 * maintaining the architectural mandate that Core only depends on Shared.
 */
class LocalizationService implements Contract
{
    /**
     * Supported locales with metadata.
     *
     * @var array<string, array{name: string, icon: string}>
     */
    private const SUPPORTED_LOCALES = [
        'en' => ['name' => 'English', 'icon' => 'us'],
        'id' => ['name' => 'Indonesian', 'icon' => 'id'],
    ];

    /**
     * {@inheritdoc}
     */
    public function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale(string $locale): bool
    {
        if (! isset(self::SUPPORTED_LOCALES[$locale])) {
            return false;
        }

        Session::put('locale', $locale);
        App::setLocale($locale);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLocale(): string
    {
        return App::getLocale();
    }
}
