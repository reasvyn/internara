<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\UI\Livewire\LanguageSwitcher;
use Modules\UI\Services\Contracts\LocalizationService;

test('it renders supported locales', function () {
    $service = mock(LocalizationService::class);
    $service->shouldReceive('getSupportedLocales')->andReturn([
        'en' => ['name' => 'English', 'icon' => 'tabler.flag'],
        'id' => ['name' => 'Indonesia', 'icon' => 'tabler.id'],
    ]);
    app()->instance(LocalizationService::class, $service);

    Livewire::test(LanguageSwitcher::class)->assertSee('English')->assertSee('Indonesia');
});

test('it can change locale', function () {
    $service = mock(LocalizationService::class);
    $service->shouldReceive('getSupportedLocales')->andReturn(['id' => ['name' => 'ID', 'icon' => 'tabler.id']]);
    $service->shouldReceive('setLocale')->with('id')->once()->andReturn(true);
    app()->instance(LocalizationService::class, $service);

    Livewire::test(LanguageSwitcher::class)->call('changeLocale', 'id')->assertRedirect();
});
