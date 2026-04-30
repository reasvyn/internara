<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('it renders alert with correct type and title', function () {
    $html = Blade::render('<x-ui::alert type="success" title="Success Title" />');

    expect($html)
        ->toContain('alert-success')
        ->toContain('Success Title')
        ->toContain('role="alert"');
});

test('it renders description from slot', function () {
    $html = Blade::render('<x-ui::alert>Alert Description</x-ui::alert>');

    expect($html)->toContain('Alert Description');
});
