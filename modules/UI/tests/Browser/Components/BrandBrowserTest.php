<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

test('brand link navigates to home', function () {
    setting(['brand_name' => 'Internara Browser Test']);

    Route::get('/test-home', fn () => 'Home Content')->name('home');

    $this->browse(function ($browser) {
        $browser
            ->visit('/test-home')
            ->assertSee('Internara Browser Test')
            ->click('a[aria-label="Go to Internara Browser Test homepage"]')
            ->assertPathIs('/login');
    });
});
