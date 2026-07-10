<?php

declare(strict_types=1);
use App\Settings\Actions\ReadAcademicYearAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;

uses(LazilyRefreshDatabase::class);

test('get academic years action returns collection', function () {
    $action = new ReadAcademicYearAction;

    $result = $action->execute();

    expect($result)->toBeInstanceOf(Collection::class);
});
