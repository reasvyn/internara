<?php

declare(strict_types=1);

use App\Settings\Actions\GetAcademicYearsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

test('get academic years action returns collection', function () {
    $action = new GetAcademicYearsAction;

    $result = $action->execute();

    expect($result)->toBeInstanceOf(Collection::class);
});
