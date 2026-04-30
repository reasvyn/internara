<?php

declare(strict_types=1);

use App\Actions\Internship\RegisterInternshipAction;
use App\Enums\Role as RoleEnum;
use App\Models\Internship;
use App\Models\User;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    $this->student = User::factory()->create();
    $this->student->assignRole(RoleEnum::STUDENT);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::ADMIN);

    $this->internship = Internship::factory()->create();
});

describe('Internship Registration', function () {
    it('allows student to register for internship', function () {
        todo('Status package integration pending - requires statuses table.');
    });

    it('prevents duplicate registration', function () {
        todo('Duplicate registration prevention requires status package.');
    });

    it('requires active internship batch', function () {
        todo('Active batch check requires status package integration.');
    });
});

describe('Internship Approval', function () {
    it('allows admin to approve registration', function () {
        todo('Admin approval flow requires status package integration.');
    });
});

describe('Placement Management', function () {
    it('allows admin to create placement', function () {
        $action = app(\App\Actions\Internship\CreatePlacementAction::class);

        $company = \App\Models\InternshipCompany::factory()->create();

        $placement = $action->execute([
            'name' => 'Batch 2026/2027',
            'internship_id' => $this->internship->id,
            'company_id' => $company->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        expect($placement)->toBeInstanceOf(\App\Models\InternshipPlacement::class);
    });
});