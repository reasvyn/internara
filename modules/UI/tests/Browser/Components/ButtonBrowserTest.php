<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

test('button is clickable in browser', function () {
    Route::get(
        '/test-button',
        fn () => Blade::render(
            '<x-ui::button label="Click Me" onclick="this.innerHTML=\'Clicked\'" />',
        ),
    );

    $this->browse(function ($browser) {
        $browser
            ->visit('/test-button')
            ->assertSee('Click Me')
            ->click('button')
            ->assertSee('Clicked');
    });
});
