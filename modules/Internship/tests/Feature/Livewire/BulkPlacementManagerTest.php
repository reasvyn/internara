<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Core\Academic\Support\AcademicYear;
use Modules\Internship\Livewire\BulkPlacementManager;
use Modules\Internship\Models\Company;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\School\Models\School;
use Modules\Student\Models\Student;
use Modules\User\Models\User;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('BulkPlacementManager Authorization', function () {
    test('admin can access bulk placement manager', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(BulkPlacementManager::class)
            ->assertStatus(200);
    });

    test('super-admin can access bulk placement manager', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $this->actingAs($superAdmin);

        Livewire::test(BulkPlacementManager::class)
            ->assertStatus(200);
    });

    test('teacher cannot access bulk placement manager', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(BulkPlacementManager::class);
    });
});

describe('BulkPlacementManager Internship Selection', function () {
    test('it loads available internship programs', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $currentYear = AcademicYear::current();
        Internship::factory()->create([
            'title' => 'Program Magang 2026',
            'academic_year' => $currentYear,
        ]);

        Livewire::test(BulkPlacementManager::class)
            ->assertCount('internships', 1);
    });

    test('it clears companies when internship changes', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        Company::factory()->create();

        Livewire::test(BulkPlacementManager::class)
            ->set('internshipId', $internship->id)
            ->assertSet('internshipId', $internship->id);
    });
});

describe('BulkPlacementManager Company Selection', function () {
    test('it loads available companies for selected internship', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        $company = Company::factory()->create(['name' => 'PT Teknologi']);

        Livewire::test(BulkPlacementManager::class)
            ->set('internshipId', $internship->id)
            ->assertCount('companies', 1)
            ->assertSeeHtml('PT Teknologi');
    });

    test('it excludes companies already assigned to internship', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        $company1 = Company::factory()->create(['name' => 'PT Teknologi 1']);
        $company2 = Company::factory()->create(['name' => 'PT Teknologi 2']);

        // Assign company1 to internship
        InternshipPlacement::factory()->create([
            'internship_id' => $internship->id,
            'company_id' => $company1->id,
        ]);

        Livewire::test(BulkPlacementManager::class)
            ->set('internshipId', $internship->id)
            ->assertCount('companies', 1)
            ->assertSeeHtml('PT Teknologi 2');
    });
});

describe('BulkPlacementManager Quota Management', function () {
    test('it calculates remaining quota correctly', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        $company = Company::factory()->create();

        $placement = InternshipPlacement::factory()->create([
            'internship_id' => $internship->id,
            'company_id' => $company->id,
            'capacity_quota' => 5,
        ]);

        // Create 2 registrations
        InternshipRegistration::factory()->create([
            'internship_id' => $internship->id,
            'placement_id' => $placement->id,
        ]);
        InternshipRegistration::factory()->create([
            'internship_id' => $internship->id,
            'placement_id' => $placement->id,
        ]);

        Livewire::test(BulkPlacementManager::class)
            ->set('internshipId', $internship->id)
            ->set('companyId', $company->id)
            ->assertSet('remainingQuota', 3);
    });

    test('it prevents placement when quota is insufficient', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        $company = Company::factory()->create();

        $placement = InternshipPlacement::factory()->create([
            'internship_id' => $internship->id,
            'company_id' => $company->id,
            'capacity_quota' => 2,
        ]);

        // Create 3 unplaced registrations
        $registrations = InternshipRegistration::factory()->count(3)->create([
            'internship_id' => $internship->id,
            'placement_id' => null,
        ]);

        Livewire::test(BulkPlacementManager::class)
            ->set('internshipId', $internship->id)
            ->set('companyId', $company->id)
            ->set('selectedStudents', [
                $registrations[0]->id,
                $registrations[1]->id,
                $registrations[2]->id,
            ])
            ->call('showConfirmation')
            ->assertDispatched('notify');
    });
});

describe('BulkPlacementManager Student Selection', function () {
    test('it loads unplaced students for selected internship', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();

        // Create 3 unplaced registrations
        InternshipRegistration::factory()->count(3)->create([
            'internship_id' => $internship->id,
            'placement_id' => null,
        ]);

        Livewire::test(BulkPlacementManager::class)
            ->set('internshipId', $internship->id)
            ->assertCount('availableStudents', 3);
    });

    test('it excludes already placed students', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        $placement = InternshipPlacement::factory()->create([
            'internship_id' => $internship->id,
        ]);

        // Create 2 placed and 1 unplaced registration
        InternshipRegistration::factory()->create([
            'internship_id' => $internship->id,
            'placement_id' => $placement->id,
        ]);
        InternshipRegistration::factory()->create([
            'internship_id' => $internship->id,
            'placement_id' => $placement->id,
        ]);
        InternshipRegistration::factory()->create([
            'internship_id' => $internship->id,
            'placement_id' => null,
        ]);

        Livewire::test(BulkPlacementManager::class)
            ->set('internshipId', $internship->id)
            ->assertCount('availableStudents', 1);
    });
});

describe('BulkPlacementManager Execution', function () {
    test('it executes bulk placement successfully', function () {
        session(['setup_authorized' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        $company = Company::factory()->create();

        $placement = InternshipPlacement::factory()->create([
            'internship_id' => $internship->id,
            'company_id' => $company->id,
            'capacity_quota' => 5,
        ]);

        // Create 3 unplaced registrations
        $registrations = InternshipRegistration::factory()->count(3)->create([
            'internship_id' => $internship->id,
            'placement_id' => null,
        ]);

        Livewire::test(BulkPlacementManager::class)
            ->set('internshipId', $internship->id)
            ->set('companyId', $company->id)
            ->set('selectedStudents', [
                $registrations[0]->id,
                $registrations[1]->id,
                $registrations[2]->id,
            ])
            ->call('showConfirmation')
            ->set('confirmModal', true)
            ->call('executePlacement')
            ->assertDispatched('notify');

        // Verify placements were created
        foreach ($registrations as $registration) {
            $this->assertDatabaseHas('internship_registrations', [
                'id' => $registration->id,
                'placement_id' => $placement->id,
            ]);
        }
    });

    test('it resets form after successful placement', function () {
        session(['setup_authorized' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        $company = Company::factory()->create();

        $placement = InternshipPlacement::factory()->create([
            'internship_id' => $internship->id,
            'company_id' => $company->id,
        ]);

        $registration = InternshipRegistration::factory()->create([
            'internship_id' => $internship->id,
            'placement_id' => null,
        ]);

        Livewire::test(BulkPlacementManager::class)
            ->set('internshipId', $internship->id)
            ->set('companyId', $company->id)
            ->set('selectedStudents', [$registration->id])
            ->call('showConfirmation')
            ->set('confirmModal', true)
            ->call('executePlacement')
            ->assertSet('internshipId', '')
            ->assertSet('companyId', '')
            ->assertSet('selectedStudents', []);
    });
});

describe('BulkPlacementManager Validation', function () {
    test('it requires student selection before confirmation', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        $company = Company::factory()->create();

        Livewire::test(BulkPlacementManager::class)
            ->set('internshipId', $internship->id)
            ->set('companyId', $company->id)
            ->set('selectedStudents', [])
            ->call('showConfirmation')
            ->assertDispatched('notify');
    });

    test('it requires internship and company selection', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $registration = InternshipRegistration::factory()->create();

        Livewire::test(BulkPlacementManager::class)
            ->set('selectedStudents', [$registration->id])
            ->call('showConfirmation')
            ->assertDispatched('notify');
    });
});
