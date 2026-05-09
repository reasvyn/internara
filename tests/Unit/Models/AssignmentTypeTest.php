<?php

declare(strict_types=1);

use App\Models\Assignment;
use App\Models\AssignmentType;
use Database\Factories\AssignmentFactory;
use Database\Factories\AssignmentTypeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $type = AssignmentTypeFactory::new()->create();

    expect($type)->toBeInstanceOf(AssignmentType::class)
        ->and($type->id)->toBeUuid();
});

it('has fillable attributes', function () {
    $type = AssignmentType::create([
        'name' => 'Essay',
        'slug' => 'essay',
        'group' => 'academic',
        'description' => 'Write an essay',
    ]);

    expect($type->name)->toBe('Essay')
        ->and($type->slug)->toBe('essay')
        ->and($type->group)->toBe('academic');
});

it('has many assignments', function () {
    $type = AssignmentTypeFactory::new()->create();
    AssignmentFactory::new()->count(2)->create(['assignment_type_id' => $type->id]);

    expect($type->assignments)->toHaveCount(2)
        ->and($type->assignments->first())->toBeInstanceOf(Assignment::class);
});
