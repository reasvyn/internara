<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    view()->share('errors', new \Illuminate\Support\ViewErrorBag);
});

test('it renders input with label and aria-label', function () {
    $html = Blade::render('<x-ui::input label="Email Address" placeholder="Enter your email" />');

    expect($html)
        ->toContain('Email Address')
        ->toContain('aria-label="Email Address"')
        ->toContain('placeholder="Enter your email"');
});

test('it falls back to placeholder for aria-label if label is missing', function () {
    $html = Blade::render('<x-ui::input placeholder="Search..." />');

    expect($html)->toContain('aria-label="Search..."');
});
