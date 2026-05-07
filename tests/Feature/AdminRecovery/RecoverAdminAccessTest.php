<?php

declare(strict_types=1);

use App\Actions\Setup\RecoverAdminAccessAction;
use App\Enums\Auth\AccountStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
});

it('creates a new admin user when isReset is false', function () {
    $action = app(RecoverAdminAccessAction::class);
    $email = 'admin@internara.test';
    $password = 'secure-password';

    $user = $action->execute(email: $email, password: $password);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->email)->toBe($email);
    expect($user->name)->toBe('Recovery Admin');
    expect($user->username)->toStartWith('admin_');

    assertDatabaseHas('users', ['id' => $user->id, 'email' => $email]);
    assertDatabaseHas('profiles', ['user_id' => $user->id]);

    expect($user->latestStatus()->name)->toBe(AccountStatus::PROTECTED->value);
    expect($user->hasRole('super_admin'))->toBeTrue();

    $activity = Activity::where('subject_id', $user->id)
        ->where('subject_type', User::class)
        ->first();
    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('admin_recovered');
    expect($activity->log_name)->toBe('Setup');
    expect($activity->subject_type)->toBe(User::class);
    expect($activity->subject_id)->toBe($user->id);
    expect($activity->properties->get('payload')['type'])->toBe('create');
});

it('resets an existing admin user when isReset is true', function () {
    $existing = User::factory()
        ->withPassword('old-password')
        ->create(['locked_at' => now(), 'locked_reason' => 'manual_lock']);
    $existing->setStatus(AccountStatus::SUSPENDED);

    $action = app(RecoverAdminAccessAction::class);
    $newPassword = 'new-secure-password';

    $user = $action->execute(email: $existing->email, password: $newPassword, isReset: true);

    expect($user->id)->toBe($existing->id);
    expect($user->locked_at)->toBeNull();
    expect($user->locked_reason)->toBeNull();

    expect($user->latestStatus()->name)->toBe(AccountStatus::VERIFIED->value);
    expect($user->hasRole('super_admin'))->toBeTrue();

    $activity = Activity::where('subject_id', $user->id)
        ->where('subject_type', User::class)
        ->orderByDesc('id')
        ->first();
    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('admin_recovered');
    expect($activity->properties->get('payload')['type'])->toBe('reset');
});

it('throws an exception when resetting a non-existent user', function () {
    $action = app(RecoverAdminAccessAction::class);

    $action->execute(email: 'nonexistent@test.com', password: 'password', isReset: true);
})->throws(ModelNotFoundException::class);

it('assigns a custom role instead of default super_admin', function () {
    $action = app(RecoverAdminAccessAction::class);
    $email = 'custom-role@internara.test';

    $user = $action->execute(email: $email, password: 'password', role: 'admin');

    expect($user->hasRole('super_admin'))->toBeFalse();
    expect($user->hasRole('admin'))->toBeTrue();
});
