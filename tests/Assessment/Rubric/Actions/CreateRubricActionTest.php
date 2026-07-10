<?php

declare(strict_types=1);

use App\Assessment\Rubric\Actions\CreateRubricAction;
use App\Assessment\Rubric\Models\Rubric;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates rubric as active by default', function () {
    actingAsAdmin();

    $rubric = app(CreateRubricAction::class)->execute(
        name: 'Final Assessment Rubric',
    );

    expect($rubric)->toBeInstanceOf(Rubric::class);
    expect($rubric->is_active)->toBeTrue();
    expect($rubric->name)->toBe('Final Assessment Rubric');
});

test('creates rubric as inactive when specified', function () {
    actingAsAdmin();

    $rubric = app(CreateRubricAction::class)->execute(
        name: 'Inactive Rubric',
        isActive: false,
    );

    expect($rubric->is_active)->toBeFalse();
});
