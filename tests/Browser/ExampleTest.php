<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

test('it performs a basic browser test', function () {
    // Note: Temporary routes defined in browser tests usually require
    // the application to be running in the same process or a shared environment.
    Route::get('/example-test', fn () => 'welcome');

    $page = visit('/example-test');
    $page->assertSee('welcome');
});
