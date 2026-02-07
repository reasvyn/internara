<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

test('avatar displays correctly in browser', function () {
    Route::get('/test-avatar', fn () => Blade::render('<x-ui::avatar title="Jane Doe" />'));

    $this->browse(function ($browser) {
        $browser
            ->visit('/test-avatar')
            ->assertSee('J')
            ->assertAriaAttribute('.avatar', 'label', 'Jane Doe');
    });
});
