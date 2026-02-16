<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Dusk\Browser;

test('it performs a basic browser test', function () {
    setting()->override(['app_installed' => true]);

    Route::get('/example', fn () => 'welcome');

    $this->browse(function (Browser $browser) {
        $browser->visit('/example')->assertPathIs('/example');
    });
});
