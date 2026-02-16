<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('it renders button with correct priority class', function () {
    $html = Blade::render('<x-ui::button variant="primary" label="Click me" />');
    expect($html)->toContain('btn-primary');

    $html = Blade::render('<x-ui::button variant="secondary" label="Click me" />');
    expect($html)->toContain('btn-outline');
});

test('it enforces minimum touch target for primary buttons', function () {
    $html = Blade::render('<x-ui::button variant="primary" label="Click me" />');
    expect($html)->toContain('min-h-[2.75rem]');
});

test('it handles aria-label correctly', function () {
    $html = Blade::render('<x-ui::button label="Search" icon="tabler.search" />');
    expect($html)->toContain('aria-label="Search"');
});
