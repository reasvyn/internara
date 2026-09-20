<?php

declare(strict_types=1);

use App\Modules\Assignment\Livewire\AssignmentManager;
use App\Modules\Assignment\Models\Assignment;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('T657Z-FR-ASG-009: manager searches by assignment fields and applies status, type, and mandatory filters', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $alpha = Internship::factory()->create(['name' => 'Alpha Internship']);
    $beta = Internship::factory()->create(['name' => 'Beta Internship']);
    Assignment::factory()->create([
        'internship_id' => $alpha->id,
        'title' => 'Alpha project brief',
        'assignment_type' => 'project',
        'is_mandatory' => true,
    ]);
    Assignment::factory()->published()->create([
        'internship_id' => $beta->id,
        'title' => 'Beta report brief',
        'assignment_type' => 'report',
        'is_mandatory' => false,
    ]);

    $this->actingAs($admin);

    Livewire::test(AssignmentManager::class)
        ->set('search', 'Alpha Internship')
        ->assertSee('Alpha project brief')
        ->assertDontSee('Beta report brief');

    Livewire::test(AssignmentManager::class)
        ->set('filters.status', 'published')
        ->set('filters.assignment_type', 'report')
        ->set('filters.is_mandatory', 'no')
        ->assertSee('Beta report brief')
        ->assertDontSee('Alpha project brief');
});

test('T657Z-FR-ASG-012: manager validates and persists the create form through the action boundary', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $internship = Internship::factory()->create();
    $this->actingAs($admin);

    Livewire::test(AssignmentManager::class)
        ->call('create')
        ->set('formData.assignment_type', 'essay')
        ->set('formData.internship_id', $internship->id)
        ->set('formData.title', 'Reflective essay')
        ->set('formData.description', 'Explain the placement learnings.')
        ->set('formData.is_mandatory', true)
        ->set('formData.due_date', now()->addWeek()->toDateString())
        ->call('save')
        ->assertSet('assignmentModal', false);

    $assignment = Assignment::query()->where('title', 'Reflective essay')->firstOrFail();
    expect($assignment->assignment_type)->toBe('essay')
        ->and($assignment->internship_id)->toBe($internship->id)
        ->and($assignment->is_mandatory)->toBeTrue();
});
