<?php

declare(strict_types=1);

use App\Livewire\Assessment\RubricManager;
use App\Models\Competency;
use App\Models\Indicator;
use App\Models\Rubric;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'teacher', 'student', 'supervisor'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create()->assignRole('admin');
});

it('admin can view rubrics page', function () {
    Rubric::factory()->create(['name' => 'PKL Rubric']);

    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->assertSee('PKL Rubric');
});

it('shows empty state when no rubrics', function () {
    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->assertSee('No rubrics yet');
});

it('admin can create a rubric', function () {
    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->call('addRubric')
        ->set('rubricForm.name', 'PKL 2026 Assessment')
        ->set('rubricForm.description', 'Final assessment rubric for PKL 2026')
        ->call('saveRubric')
        ->assertHasNoErrors();

    expect(Rubric::where('name', 'PKL 2026 Assessment')->exists())->toBeTrue();
});

it('validates rubric name is required', function () {
    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->call('addRubric')
        ->call('saveRubric')
        ->assertHasErrors(['rubricForm.name']);
});

it('admin can edit a rubric', function () {
    $rubric = Rubric::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->call('editRubric', $rubric->id)
        ->set('rubricForm.name', 'Updated Name')
        ->call('saveRubric')
        ->assertHasNoErrors();

    expect($rubric->fresh()->name)->toBe('Updated Name');
});

it('admin can delete a rubric', function () {
    $rubric = Rubric::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->call('removeRubric', $rubric->id);

    expect(Rubric::find($rubric->id))->toBeNull();
});

it('admin can create a competency within a rubric', function () {
    $rubric = Rubric::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->call('addCompetency', $rubric->id)
        ->set('competencyForm.name', 'Teacher Assessment')
        ->set('competencyForm.weight', 50)
        ->set('competencyForm.evaluator_role', 'teacher')
        ->set('competencyForm.order', 1)
        ->call('saveCompetency')
        ->assertHasNoErrors();

    expect(Competency::where('rubric_id', $rubric->id)->count())->toBe(1);
});

it('validates competency evaluator role is required', function () {
    $rubric = Rubric::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->call('addCompetency', $rubric->id)
        ->set('competencyForm.name', 'Test')
        ->set('competencyForm.weight', 50)
        ->call('saveCompetency')
        ->assertHasErrors(['competencyForm.evaluator_role']);
});

it('admin can create an indicator within a competency', function () {
    $competency = Competency::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->call('addIndicator', $competency->id)
        ->set('indicatorForm.name', 'Report Quality')
        ->set('indicatorForm.max_score', 100)
        ->set('indicatorForm.weight', 50)
        ->set('indicatorForm.order', 1)
        ->call('saveIndicator')
        ->assertHasNoErrors();

    expect(Indicator::where('competency_id', $competency->id)->count())->toBe(1);
});

it('admin can delete a competency with cascade', function () {
    $competency = Competency::factory()->has(Indicator::factory()->count(2))->create();

    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->call('removeCompetency', $competency->id);

    expect(Competency::find($competency->id))->toBeNull();
    expect(Indicator::where('competency_id', $competency->id)->count())->toBe(0);
});

it('deleting rubric cascades to competencies and indicators', function () {
    $rubric = Rubric::factory()
        ->has(Competency::factory()->has(Indicator::factory()->count(2))->count(2))
        ->create();

    Livewire::actingAs($this->admin)
        ->test(RubricManager::class)
        ->call('removeRubric', $rubric->id);

    expect(Rubric::find($rubric->id))->toBeNull();
    expect(Competency::where('rubric_id', $rubric->id)->count())->toBe(0);
});
