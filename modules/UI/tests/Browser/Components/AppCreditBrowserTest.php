<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

test('app credit contains correct github link', function () {
    setting([
        'app_author' => 'Reas Vyn',
        'app_github' => 'https://github.com/reasnov',
    ]);

    Route::get('/test-credit', fn () => Blade::render('<x-ui::app-credit />'));

    $this->browse(function ($browser) {
        $browser
            ->visit('/test-credit')
            ->assertSee('Reas Vyn')
            ->assertAttribute('a', 'href', 'https://github.com/reasnov');
    });
});
