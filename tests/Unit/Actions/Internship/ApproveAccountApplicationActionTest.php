<?php

declare(strict_types=1);

use App\Actions\Internship\ApproveAccountApplicationAction;
use App\Models\AccountApplication;
use App\Models\Registration;
use Database\Factories\InternshipFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

it('approves an application and creates registration', function () {
    $internship = InternshipFactory::new()->create();
    $application = AccountApplication::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'internship_id' => $internship->id,
        'status' => 'pending',
    ]);
    $admin = UserFactory::new()->create()->assignRole('super_admin');

    $registration = app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);

    expect($registration)->toBeInstanceOf(Registration::class)
        ->and($application->fresh()->status)->toBe('approved');
});

it('throws RuntimeException when application is not pending', function () {
    $internship = InternshipFactory::new()->create();
    $application = AccountApplication::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'internship_id' => $internship->id,
        'status' => 'approved',
    ]);
    $admin = UserFactory::new()->create()->assignRole('super_admin');

    expect(fn () => app(ApproveAccountApplicationAction::class)->execute($application->id, $admin))
        ->toThrow(RuntimeException::class, 'Application is not in pending status.');
});
