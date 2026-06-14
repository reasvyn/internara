<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\AccountApplication\Actions\ApproveAccountApplicationAction;
use App\Enrollment\AccountApplication\Models\AccountApplication;
use App\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('approves pending application and creates user and registration', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $application = AccountApplication::factory()->pending()->create();

    $registration = app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);

    expect($registration)->not->toBeNull();
    expect($application->fresh()->status->value)->toBe('approved');
    expect($application->fresh()->processed_by)->toBe($admin->id);
    expect($application->fresh()->processed_at)->not->toBeNull();
    $this->assertDatabaseHas('users', ['email' => $application->email]);
    $this->assertModelExists($registration);
});

test('throws exception when application is not pending', function () {
    $admin = User::factory()->create();
    $application = AccountApplication::factory()->approved()->create();

    expect(fn () => app(ApproveAccountApplicationAction::class)->execute($application->id, $admin))
        ->toThrow(RejectedException::class);
});

test('throws exception for non-existent application', function () {
    $admin = User::factory()->create();

    expect(fn () => app(ApproveAccountApplicationAction::class)->execute('non-existent-id', $admin))
        ->toThrow(ModelNotFoundException::class);
});
