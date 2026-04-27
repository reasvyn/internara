<?php

declare(strict_types=1);

namespace Modules\UI\Tests\Unit\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Modules\UI\Services\LocalizationService;

test('it returns supported locales', function () {
    $service = new LocalizationService();
    $locales = $service->getSupportedLocales();

    expect($locales)->toBeArray()->and($locales)->toHaveKey('en')->and($locales)->toHaveKey('id');
});

test('it can set locale', function () {
    $service = new LocalizationService();

    $result = $service->setLocale('id');

    expect($result)
        ->toBeTrue()
        ->and(App::getLocale())
        ->toBe('id')
        ->and(Session::get('locale'))
        ->toBe('id');
});

test('it fails to set unsupported locale', function () {
    $service = new LocalizationService();

    $result = $service->setLocale('fr');

    expect($result)->toBeFalse();
});

test('it returns current locale', function () {
    $service = new LocalizationService();
    App::setLocale('en');

    expect($service->getCurrentLocale())->toBe('en');
});
