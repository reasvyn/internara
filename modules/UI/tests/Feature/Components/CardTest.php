<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('it renders card with default styles', function () {
    $html = Blade::render('<x-ui::card>Card Content</x-ui::card>');

    expect($html)->toContain('Card Content')->toContain('bg-base-100')->toContain('rounded-2xl');
});

test('it accepts custom classes', function () {
    $html = Blade::render('<x-ui::card class="p-10">Card Content</x-ui::card>');

    expect($html)->toContain('p-10');
});
