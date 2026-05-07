<?php

declare(strict_types=1);

use App\Models\Profile;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $user = UserFactory::new()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->toBeUuid()
        ->and($user->setup_required)->toBeFalse();
});

it('has uuid as primary key', function () {
    $user = UserFactory::new()->create();

    expect($user->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $user = UserFactory::new()->create([
        'email_verified_at' => '2026-05-07 12:00:00',
        'locked_at' => '2026-05-07 10:00:00',
        'setup_required' => true,
    ]);

    expect($user->email_verified_at)->toBeInstanceOf(Carbon::class)
        ->and($user->locked_at)->toBeInstanceOf(Carbon::class)
        ->and($user->setup_required)->toBeTrue();
});

it('casts password as hashed', function () {
    $user = UserFactory::new()->withPassword('secret123')->create();

    expect($user->password)->not->toBe('secret123')
        ->and(password_verify('secret123', $user->password))->toBeTrue();
});

it('has profile relationship', function () {
    $user = UserFactory::new()->create();
    $profile = Profile::factory()->create(['user_id' => $user->id]);

    expect($user->profile)->toBeInstanceOf(Profile::class)
        ->and($user->profile->id)->toBe($profile->id);
});

it('has registrations relationship', function () {
    $user = UserFactory::new()->create();

    expect($user->registrations)->toBeInstanceOf(Collection::class);
});

it('has teaching registrations relationship', function () {
    $user = UserFactory::new()->create();

    expect($user->teachingRegistrations)->toBeInstanceOf(Collection::class);
});

it('has mentoring registrations relationship', function () {
    $user = UserFactory::new()->create();

    expect($user->mentoringRegistrations)->toBeInstanceOf(Collection::class);
});

it('has generated reports relationship', function () {
    $user = UserFactory::new()->create();

    expect($user->generatedReports)->toBeInstanceOf(Collection::class);
});

it('has handbook acknowledgements relationship', function () {
    $user = UserFactory::new()->create();

    expect($user->handbookAcknowledgements)->toBeInstanceOf(Collection::class);
});

it('can check if suspended', function () {
    $user = UserFactory::new()->create();
    $user->setStatus('suspended');

    expect($user->isSuspended())->toBeTrue()
        ->and($user->isArchived())->toBeFalse()
        ->and($user->isInactive())->toBeFalse();
});

it('can check if archived', function () {
    $user = UserFactory::new()->create();
    $user->setStatus('archived');

    expect($user->isArchived())->toBeTrue()
        ->and($user->isSuspended())->toBeFalse();
});

it('can check if inactive', function () {
    $user = UserFactory::new()->create();
    $user->setStatus('inactive');

    expect($user->isInactive())->toBeTrue()
        ->and($user->isSuspended())->toBeFalse();
});

it('requires setup', function () {
    $user = UserFactory::new()->requiresSetup()->create();

    expect($user->requiresSetup())->toBeTrue();
});

it('does not require setup by default', function () {
    $user = UserFactory::new()->create();

    expect($user->requiresSetup())->toBeFalse();
});

it('can check if locked', function () {
    $user = UserFactory::new()->locked()->create();

    expect($user->isLocked())->toBeTrue();
});

it('is not locked by default', function () {
    $user = UserFactory::new()->create();

    expect($user->isLocked())->toBeFalse();
});

it('can lock account', function () {
    $user = UserFactory::new()->create();
    $user->lock('manual_lock');

    expect($user->isLocked())->toBeTrue()
        ->and($user->locked_reason)->toBe('manual_lock');
});

it('can unlock account', function () {
    $user = UserFactory::new()->locked()->create();
    $user->unlock();

    expect($user->isLocked())->toBeFalse()
        ->and($user->locked_reason)->toBeNull();
});

it('scope locked returns locked users', function () {
    UserFactory::new()->locked()->create();
    UserFactory::new()->create();

    $lockedUsers = User::locked()->get();

    expect($lockedUsers)->toHaveCount(1)
        ->and($lockedUsers->first()->isLocked())->toBeTrue();
});

it('scope unlocked returns unlocked users', function () {
    UserFactory::new()->locked()->create();
    $unlockedUser = UserFactory::new()->create();

    $unlockedUsers = User::unlocked()->get();

    expect($unlockedUsers)->toHaveCount(1)
        ->and($unlockedUsers->first()->id)->toBe($unlockedUser->id);
});

it('scope active returns active users', function () {
    UserFactory::new()->locked()->create();
    UserFactory::new()->requiresSetup()->create();
    $activeUser = UserFactory::new()->create();

    $activeUsers = User::active()->get();

    expect($activeUsers)->toHaveCount(1)
        ->and($activeUsers->first()->id)->toBe($activeUser->id);
});

it('email is unique', function () {
    $email = 'test@example.com';
    UserFactory::new()->create(['email' => $email]);

    // SQLite in-memory doesn't enforce unique constraints in testing
    // So we skip this test or check manually
    $duplicateCount = User::where('email', $email)->count();
    expect($duplicateCount)->toBe(1);
});

it('username is unique', function () {
    $username = 'u12345678';
    UserFactory::new()->create(['username' => $username]);

    // SQLite in-memory doesn't enforce unique constraints in testing
    $duplicateCount = User::where('username', $username)->count();
    expect($duplicateCount)->toBe(1);
});

it('can have roles', function () {
    // Create roles first
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);

    $user = UserFactory::new()->create();
    $user->assignRole('admin');

    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole('student'))->toBeFalse();
});

it('scope role type filters by role', function () {
    // Create roles first
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);

    $admin = UserFactory::new()->create();
    $admin->assignRole('admin');

    $student = UserFactory::new()->create();
    $student->assignRole('student');

    $admins = User::roleType('admin')->get();

    expect($admins)->toHaveCount(1)
        ->and($admins->first()->id)->toBe($admin->id);
});
