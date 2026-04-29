<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('it renders avatar with image', function () {
    $html = Blade::render(
        '<x-ui::avatar image="https://example.com/avatar.jpg" title="John Doe" />',
    );

    expect($html)
        ->toContain('https://example.com/avatar.jpg')
        ->toContain('alt="John Doe"')
        ->toContain('aria-label="John Doe"');
});

test('it renders placeholder with first letter when image is missing', function () {
    $html = Blade::render('<x-ui::avatar title="John Doe" />');

    expect($html)->toContain('J')->toContain('aria-label="John Doe"');
});

test('it renders default icon when both image and title are missing', function () {
    app()->setLocale('en');
    $html = Blade::render('<x-ui::avatar />');

    expect($html)->toContain('aria-label="User Avatar"');
});
