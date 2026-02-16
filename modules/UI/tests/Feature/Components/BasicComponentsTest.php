<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    view()->share('errors', new \Illuminate\Support\ViewErrorBag);
});

test('it renders badge with correct priority class', function () {
    $html = Blade::render('<x-ui::badge variant="primary">New</x-ui::badge>');
    expect($html)->toContain('badge-primary')->toContain('New');
});

test('it renders checkbox with label and aria-label', function () {
    $html = Blade::render('<x-ui::checkbox label="Accept Terms" />');
    expect($html)->toContain('Accept Terms')->toContain('aria-label="Accept Terms"');
});

test('it renders icon as aria-hidden by default', function () {
    $html = Blade::render('<x-ui::icon name="tabler.home" />');
    expect($html)->toContain('aria-hidden="true"');
});

test('it renders dropdown with correct trigger label', function () {
    $html = Blade::render('<x-ui::dropdown label="Actions" />');
    expect($html)->toContain('Actions')->toContain('role="menu"');
});
