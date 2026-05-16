<?php

declare(strict_types=1);

use App\Entities\School\SchoolState;
use App\Models\School;

it('can be instantiated from model', function () {
    $model = Mockery::mock(School::class);
    $model->shouldReceive('getAttribute')->with('id')->andReturn('some-uuid');

    $entity = SchoolState::fromModel($model);

    expect($entity)->toBeInstanceOf(SchoolState::class);
});

it('can be created when model has no id and single record is enabled', function () {
    $model = Mockery::mock(School::class);
    $model->shouldReceive('getAttribute')->with('id')->andReturn(null);

    $entity = SchoolState::fromModel($model);

    expect($entity->canBeCreated())->toBeTrue();
});

it('cannot be created when model has an id and single record is enabled', function () {
    $model = Mockery::mock(School::class);
    $model->shouldReceive('getAttribute')->with('id')->andReturn('existing-uuid');

    $entity = SchoolState::fromModel($model);

    expect($entity->canBeCreated())->toBeFalse();
});

it('can be created even if model has id when single record is disabled', function () {
    config(['school.single_record' => false]);

    $model = Mockery::mock(School::class);
    $model->shouldReceive('getAttribute')->with('id')->andReturn('some-uuid');

    $entity = SchoolState::fromModel($model);

    expect($entity->canBeCreated())->toBeTrue();
});
