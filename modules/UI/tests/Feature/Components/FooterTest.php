<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('it renders footer with current year and brand name', function () {
    setting(['brand_name' => 'Internara Academy']);
    $year = now()->format('Y');

    $html = Blade::render('<x-ui::footer />');

    expect($html)->toContain($year)->toContain('Internara Academy');
});
