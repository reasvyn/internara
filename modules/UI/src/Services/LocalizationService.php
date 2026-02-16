<?php

declare(strict_types=1);

namespace Modules\UI\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Modules\UI\Services\Contracts\LocalizationService as Contract;

/**
 * Class LocalizationService
 *
 * Handles the logic for retrieving and switching application locales.
 */
class LocalizationService implements Contract
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedLocales(): array
    {
        return (array) config('ui.locales', []);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale(string $locale): bool
    {
        if (!array_key_exists($locale, $this->getSupportedLocales())) {
            return false;
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

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
