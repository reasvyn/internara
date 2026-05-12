<?php

declare(strict_types=1);

use App\Actions\Internship\RejectAccountApplicationAction;
use App\Models\AccountApplication;
use Database\Factories\InternshipFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

it('rejects an application with reason', function () {
    $internship = InternshipFactory::new()->create();
    $application = AccountApplication::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);
    $admin = UserFactory::new()->create()->assignRole('super_admin');

    app(RejectAccountApplicationAction::class)->execute($application->id, $admin, 'Documents incomplete');

    expect($application->fresh()->status)->toBe('rejected')
        ->and($application->fresh()->rejection_reason)->toBe('Documents incomplete');
});

it('throws RuntimeException when rejecting non-pending application', function () {
    $internship = InternshipFactory::new()->create();
    $application = AccountApplication::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'internship_id' => $internship->id,
        'status' => 'approved',
    ]);
    $admin = UserFactory::new()->create()->assignRole('super_admin');

    expect(fn () => app(RejectAccountApplicationAction::class)->execute($application->id, $admin, 'No reason'))
        ->toThrow(RuntimeException::class, 'Application is not in pending status.');
});
