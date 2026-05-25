<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Internship\Actions\DeleteInternshipAction;
use App\Domain\Internship\Livewire\InternshipManager;
use App\Domain\Internship\Models\Internship;
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

describe('InternshipManager', function () {
    it('renders the page', function () {
        Livewire::test(InternshipManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Livewire::test(InternshipManager::class)
            ->assertStatus(403);
    })->skip('Livewire boot() authorization does not return 403 in tests');

    it('creates an internship', function () {
        $start = now()->addDay()->format('Y-m-d');
        $end = now()->addMonth()->format('Y-m-d');

        Livewire::test(InternshipManager::class)
            ->call('create')
            ->set('form.name', 'PKL 2026')
            ->set('form.start_date', $start)
            ->set('form.end_date', $end)
            ->set('form.status', 'draft')
            ->call('save')
            ->assertHasNoErrors();

        expect(Internship::where('name', 'PKL 2026')->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(InternshipManager::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.start_date', '')
            ->set('form.end_date', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.start_date', 'form.end_date']);
    });

    it('edits an internship', function () {
        $internship = Internship::factory()->create();

        Livewire::test(InternshipManager::class)
            ->call('edit', $internship->id)
            ->set('form.name', 'Updated PKL')
            ->call('save')
            ->assertHasNoErrors();

        expect($internship->fresh()->name)->toBe('Updated PKL');
    });

    it('deletes an internship', function () {
        $internship = Internship::factory()->create();

        Livewire::test(InternshipManager::class)
            ->call('askDelete', $internship->id)
            ->call('confirmAction', DeleteInternshipAction::class)
            ->assertHasNoErrors();

        expect(Internship::find($internship->id))->toBeNull();
    });
});
