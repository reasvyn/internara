<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('dashboard layout renders title and sidebar slot', function () {
    $html = Blade::render(
        '<x-ui::layouts.dashboard title="Test Page">Content</x-ui::layouts.dashboard>',
    );

    expect($html)->toContain('Test Page')->toContain('Content')->toContain('id="main-content"');
});

test('dashboard layout contains navbar and hamburger for mobile', function () {
    $html = Blade::render('<x-ui::layouts.dashboard>Content</x-ui::layouts.dashboard>');

    expect($html)->toContain('aria-label="Open menu"')->toContain('main-drawer');
});
