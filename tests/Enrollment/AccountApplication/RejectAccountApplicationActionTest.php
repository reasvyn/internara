<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\AccountApplication\Actions\RejectAccountApplicationAction;
use App\Enrollment\AccountApplication\Models\AccountApplication;
use App\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('rejects pending application with reason', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $application = AccountApplication::factory()->pending()->create();

    app(RejectAccountApplicationAction::class)->execute($application->id, $admin, 'Incomplete documents');

    expect($application->fresh()->status->value)->toBe('rejected');
    expect($application->fresh()->rejection_reason)->toBe('Incomplete documents');
    expect($application->fresh()->processed_by)->toBe($admin->id);
    expect($application->fresh()->processed_at)->not->toBeNull();
});

test('throws exception when rejecting already approved application', function () {
    $admin = User::factory()->create();
    $application = AccountApplication::factory()->approved()->create();

    expect(fn () => app(RejectAccountApplicationAction::class)->execute($application->id, $admin, 'Too late'))
        ->toThrow(RejectedException::class);
});

test('throws exception for non-existent application', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect(fn () => app(RejectAccountApplicationAction::class)->execute('non-existent-id', $admin, 'No reason'))
        ->toThrow(ModelNotFoundException::class);
});
