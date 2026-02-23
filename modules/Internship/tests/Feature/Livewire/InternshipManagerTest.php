<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Core\Academic\Support\AcademicYear;
use Modules\Internship\Livewire\InternshipManager;
use Modules\Internship\Models\Internship;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\School\Models\School;
use Modules\User\Models\User;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('InternshipManager Component', function () {
    test('it renders internship programs correctly', function () {
        $admin = User::factory()->create();
        $admin->givePermissionTo('internship.view');
        $this->actingAs($admin);

        Internship::factory()->create(['title' => 'Program Magang 2026']);

        Livewire::test(InternshipManager::class)
            ->assertStatus(200)
            ->assertSee('Program Magang 2026');
    });

    test('it pre-fills current academic year when adding a new program', function () {
        session(['setup_authorized' => true]);
        $currentYear = AcademicYear::current();

        Livewire::test(InternshipManager::class)
            ->call('add')
            ->assertSet('form.academic_year', $currentYear);
    });

    test('it can create a new internship program', function () {
        session(['setup_authorized' => true]);
        $school = School::factory()->create();

        Livewire::test(InternshipManager::class)
            ->call('add')
            ->set('form.title', 'New Internship Program')
            ->set('form.academic_year', '2025/2026')
            ->set('form.semester', 'Ganjil')
            ->set('form.date_start', '2025-07-01')
            ->set('form.date_finish', '2025-12-31')
            ->set('form.school_id', $school->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formModal', false);

        $this->assertDatabaseHas('internships', [
            'title' => 'New Internship Program',
            'academic_year' => '2025/2026',
        ]);
    });
});
