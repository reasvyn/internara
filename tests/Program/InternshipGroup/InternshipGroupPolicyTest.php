<?php

declare(strict_types=1);

use App\Program\InternshipGroup\Models\InternshipGroup;
use App\Program\InternshipGroup\Policies\InternshipGroupPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->policy = new InternshipGroupPolicy;
});

test('any user can view groups', function () {
    $user = new class extends User {};

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('guest can view groups', function () {
    expect($this->policy->viewAny(null))->toBeTrue();
});

test('admin can create group', function () {
    $user = new class extends User
    {
        public function hasAnyRole(...$roles): bool
        {
            foreach ($roles as $role) {
                if (is_array($role) && in_array('admin', $role, true)) {
                    return true;
                }
                if ($role === 'admin' || $role === 'super_admin') {
                    return true;
                }
            }

            return false;
        }
    };
    $user->id = 1;

    expect($this->policy->create($user))->toBeTrue();
});

test('non-admin cannot create group', function () {
    $user = new class extends User
    {
        public function hasAnyRole(...$roles): bool
        {
            return false;
        }
    };
    $user->id = 1;

    expect($this->policy->create($user))->toBeFalse();
});

test('admin can update group', function () {
    $user = new class extends User
    {
        public function hasAnyRole(...$roles): bool
        {
            foreach ($roles as $role) {
                if (is_array($role) && in_array('admin', $role, true)) {
                    return true;
                }
                if ($role === 'admin' || $role === 'super_admin') {
                    return true;
                }
            }

            return false;
        }
    };
    $user->id = 1;

    $group = InternshipGroup::factory()->make();
    expect($this->policy->update($user, $group))->toBeTrue();
});

test('non-admin cannot update group', function () {
    $user = new class extends User
    {
        public function hasAnyRole(...$roles): bool
        {
            return false;
        }
    };
    $user->id = 1;

    $group = InternshipGroup::factory()->make();
    expect($this->policy->update($user, $group))->toBeFalse();
});

test('admin can delete group', function () {
    $user = new class extends User
    {
        public function hasAnyRole(...$roles): bool
        {
            foreach ($roles as $role) {
                if (is_array($role) && in_array('admin', $role, true)) {
                    return true;
                }
                if ($role === 'admin' || $role === 'super_admin') {
                    return true;
                }
            }

            return false;
        }
    };
    $user->id = 1;

    $group = InternshipGroup::factory()->make();
    expect($this->policy->delete($user, $group))->toBeTrue();
});

test('non-admin cannot delete group', function () {
    $user = new class extends User
    {
        public function hasAnyRole(...$roles): bool
        {
            return false;
        }
    };
    $user->id = 1;

    $group = InternshipGroup::factory()->make();
    expect($this->policy->delete($user, $group))->toBeFalse();
});
