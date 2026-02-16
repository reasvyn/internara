<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('it renders app credit with localized labels', function () {
    app()->setLocale('en');
    setting([
        'app_author' => 'Reas Vyn',
        'app_github' => 'https://github.com/reasnov',
    ]);

    $html = Blade::render('<x-ui::app-credit />');

    expect($html)
        ->toContain('Built with')
        ->toContain('aria-label="love"')
        ->toContain('by')
        ->toContain('Reas Vyn')
        ->toContain('aria-label="Visit Reas Vyn&#039;s GitHub profile"');
});
