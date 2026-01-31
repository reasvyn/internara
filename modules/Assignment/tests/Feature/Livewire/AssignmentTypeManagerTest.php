<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Assignment\Livewire\AssignmentTypeManager;
use Modules\Assignment\Models\AssignmentType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can see assignment types list', function () {
    AssignmentType::create([
        'name' => 'Competency Certificate',
        'slug' => 'competency-certificate',
        'group' => 'certification',
    ]);

    Livewire::test(AssignmentTypeManager::class)
        ->assertSee('Competency Certificate')
        ->assertSee('certification');
});

test('admin can create a new assignment type', function () {
    Livewire::test(AssignmentTypeManager::class)
        ->set('name', 'Final Report')
        ->set('slug', 'final-report')
        ->set('group', 'report')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    expect(AssignmentType::where('slug', 'final-report')->exists())->toBeTrue();
});

test('admin can delete an assignment type', function () {
    $type = AssignmentType::create([
        'name' => 'To Be Deleted',
        'slug' => 'to-be-deleted',
        'group' => 'other',
    ]);

    Livewire::test(AssignmentTypeManager::class)
        ->call('remove', $type->id)
        ->assertDispatched('notify');

    expect(AssignmentType::where('id', $type->id)->exists())->toBeFalse();
});
