<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('it renders brand with localized accessibility labels', function () {
    app()->setLocale('en');
    setting(['brand_name' => 'Internara Test']);

    $html = Blade::render('<x-ui::brand />');

    expect($html)
        ->toContain('Internara Test')
        ->toContain('aria-label="Go to Internara Test homepage"')
        ->toContain('alt="Internara Test Logo"');
});

test('it reflects brand name from settings', function () {
    setting(['brand_name' => 'Custom School']);

    $html = Blade::render('<x-ui::brand />');

    expect($html)->toContain('Custom School');
});
