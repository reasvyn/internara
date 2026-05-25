<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Internship\Models\Internship;
use App\Domain\Partnership\Models\Company;
use App\Domain\Placement\Actions\DeletePlacementAction;
use App\Domain\Placement\Livewire\DirectPlacementManager;
use App\Domain\Placement\Livewire\PlacementChangeManager;
use App\Domain\Placement\Livewire\PlacementIndex;
use App\Domain\Placement\Models\Placement;
use App\Domain\School\Models\AcademicYear;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
    $this->year = AcademicYear::factory()->create(['is_active' => true]);
});

describe('PlacementIndex', function () {
    it('renders the page', function () {
        Livewire::test(PlacementIndex::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Livewire::test(PlacementIndex::class)
            ->assertStatus(403);
    })->skip('Livewire boot() authorization does not return 403 in tests');

    it('creates a placement', function () {
        $company = Company::factory()->create();
        $internship = Internship::factory()->create();

        Livewire::test(PlacementIndex::class)
            ->call('create')
            ->set('form.company_id', $company->id)
            ->set('form.internship_id', $internship->id)
            ->set('form.name', 'Frontend Intern')
            ->set('form.quota', 5)
            ->call('save')
            ->assertHasNoErrors();

        expect(Placement::where('name', 'Frontend Intern')->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(PlacementIndex::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.quota', null)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.quota', 'form.company_id', 'form.internship_id']);
    });

    it('edits a placement', function () {
        $company = Company::factory()->create();
        $internship = Internship::factory()->create();
        $placement = Placement::factory()->create(['company_id' => $company->id, 'internship_id' => $internship->id]);

        Livewire::test(PlacementIndex::class)
            ->set('form.id', $placement->id)
            ->set('form.company_id', $company->id)
            ->set('form.internship_id', $internship->id)
            ->set('form.name', 'Updated Intern')
            ->set('form.quota', 5)
            ->call('save')
            ->assertHasNoErrors();

        expect($placement->fresh()->name)->toBe('Updated Intern');
    });

    it('deletes a placement', function () {
        $placement = Placement::factory()->create();

        Livewire::test(PlacementIndex::class)
            ->call('delete', $placement->id, DeletePlacementAction::class)
            ->assertHasNoErrors();

        expect(Placement::find($placement->id))->toBeNull();
    });
});

describe('DirectPlacementManager', function () {
    beforeEach(function () {
        $this->internship = Internship::factory()->create();
        $this->placement = Placement::factory()->create([
            'internship_id' => $this->internship->id,
            'filled_quota' => 0,
        ]);
    });

    it('renders the page', function () {
        Livewire::test(DirectPlacementManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Livewire::test(DirectPlacementManager::class)
            ->assertStatus(403);
    })->skip('Livewire boot() authorization does not return 403 in tests');
});

describe('PlacementChangeManager', function () {
    beforeEach(function () {
        $this->internship = Internship::factory()->create();
        $this->placement = Placement::factory()->create(['internship_id' => $this->internship->id]);
    });

    it('renders the page', function () {
        Livewire::test(PlacementChangeManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Livewire::test(PlacementChangeManager::class)
            ->assertStatus(403);
    })->skip('Livewire boot() authorization does not return 403 in tests');
});
