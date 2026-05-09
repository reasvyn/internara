<?php

declare(strict_types=1);

use App\Entities\School\SchoolState;
use App\Models\School;
use Illuminate\Support\Facades\Config;

it('can be instantiated from model', function () {
    $model = Mockery::mock(School::class);
    $model->shouldReceive('newQuery->exists')->andReturn(false);

    $entity = SchoolState::fromModel($model);

    expect($entity)->toBeInstanceOf(SchoolState::class);
});

it('can be created when no school exists and single record is enabled', function () {
    Config::set('school.single_record', true);

    $model = Mockery::mock(School::class);
    $model->shouldReceive('newQuery->exists')->andReturn(false);

    $entity = SchoolState::fromModel($model);

    expect($entity->canBeCreated())->toBeTrue();
});

it('cannot be created when school exists and single record is enabled', function () {
    Config::set('school.single_record', true);

    $model = Mockery::mock(School::class);
    $model->shouldReceive('newQuery->exists')->andReturn(true);

    $entity = SchoolState::fromModel($model);

    expect($entity->canBeCreated())->toBeFalse();
});

it('can be created even if school exists when single record is disabled', function () {
    Config::set('school.single_record', false);

    $model = Mockery::mock(School::class);
    $model->shouldReceive('newQuery->exists')->andReturn(true);

    $entity = SchoolState::fromModel($model);

    expect($entity->canBeCreated())->toBeTrue();
});
