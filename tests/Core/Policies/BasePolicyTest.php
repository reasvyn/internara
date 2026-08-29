<?php

declare(strict_types=1);

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

final class TestPolicy extends BasePolicy
{
    public function view(User $user, Profile $profile): bool
    {
        return $this->isOwner($user, $profile);
    }

    public function manage(User $user, Profile $profile): bool
    {
        return $this->isOwnerOrAdmin($user, $profile);
    }

    public function related(User $user, Profile $profile): bool
    {
        return $this->isRelatedThrough($user, $profile, 'user', 'id');
    }

    public function canAdmin(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function any(User $user, array $roles): bool
    {
        return $this->hasAnyOfRoles($user, $roles);
    }
}

test('SE5Q9-FR-P1: before() allows super admins unconditionally', function () {
    $super = User::factory()->create()->assignRole('super_admin');

    expect((new TestPolicy)->before($super)->allowed())->toBeTrue();
});

test('SE5Q9-FR-P1: before() defers to the policy method for other roles', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $plain = User::factory()->create();

    expect((new TestPolicy)->before($admin))->toBeNull();
    expect((new TestPolicy)->before($plain))->toBeNull();
});

test('SE5Q9-FR-P2: isOwner() allows the owner and denies strangers', function () {
    $profile = Profile::factory()->create();
    $owner = $profile->user;
    $stranger = User::factory()->create();

    expect((new TestPolicy)->view($owner, $profile))->toBeTrue();
    expect((new TestPolicy)->view($stranger, $profile))->toBeFalse();
});

test('SE5Q9-FR-P2: isOwnerOrAdmin() allows owners and admins', function () {
    $profile = Profile::factory()->create();
    $admin = User::factory()->create()->assignRole('admin');
    $stranger = User::factory()->create();

    expect((new TestPolicy)->manage($admin, $profile))->toBeTrue();
    expect((new TestPolicy)->manage($stranger, $profile))->toBeFalse();
});

test('SE5Q9-FR-P2: isRelatedThrough() resolves ownership through a relation', function () {
    $profile = Profile::factory()->create();
    $owner = $profile->user;
    $stranger = User::factory()->create();

    expect((new TestPolicy)->related($owner, $profile))->toBeTrue();
    expect((new TestPolicy)->related($stranger, $profile))->toBeFalse();
});

test('SE5Q9-FR-P2: isAdmin() accepts super_admin and admin roles', function () {
    $super = User::factory()->create()->assignRole('super_admin');
    $admin = User::factory()->create()->assignRole('admin');
    $plain = User::factory()->create();

    expect((new TestPolicy)->canAdmin($super))->toBeTrue();
    expect((new TestPolicy)->canAdmin($admin))->toBeTrue();
    expect((new TestPolicy)->canAdmin($plain))->toBeFalse();
});

test('SE5Q9-FR-P2: hasAnyOfRoles() checks any matching role', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $plain = User::factory()->create();

    expect((new TestPolicy)->any($admin, ['admin', 'teacher']))->toBeTrue();
    expect((new TestPolicy)->any($plain, ['admin', 'teacher']))->toBeFalse();
});
