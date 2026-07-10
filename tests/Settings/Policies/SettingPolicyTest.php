<?php

declare(strict_types=1);

use App\Settings\Policies\SettingPolicy;
use App\User\Models\User;

beforeEach(function () {
    $this->policy = new SettingPolicy;
});

test('admin can view settings', function () {
    $user = new class extends User
    {
        public function hasRole($roles, ?string $guard = null): bool
        {
            return $roles === 'admin';
        }

        public function hasAnyRole(...$roles): bool
        {
            return true;
        }
    };
    $user->id = 1;

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('non-admin cannot view settings', function () {
    $user = new class extends User
    {
        public function hasRole($roles, ?string $guard = null): bool
        {
            return false;
        }

        public function hasAnyRole(...$roles): bool
        {
            return false;
        }
    };
    $user->id = 2;

    expect($this->policy->viewAny($user))->toBeFalse();
});

test('super admin can create update and delete settings', function () {
    $user = new class extends User
    {
        public function hasRole($roles, ?string $guard = null): bool
        {
            return $roles === 'super_admin' || $roles === 'superadmin';
        }

        public function hasAnyRole(...$roles): bool
        {
            return true;
        }
    };
    $user->id = 3;

    expect($this->policy->create($user))->toBeTrue();
    expect($this->policy->update($user))->toBeTrue();
    expect($this->policy->delete($user))->toBeTrue();
});

test('non-super-admin cannot mutate settings', function () {
    $user = new class extends User
    {
        public function hasRole($roles, ?string $guard = null): bool
        {
            return false;
        }

        public function hasAnyRole(...$roles): bool
        {
            return false;
        }
    };
    $user->id = 4;

    expect($this->policy->create($user))->toBeFalse();
    expect($this->policy->update($user))->toBeFalse();
    expect($this->policy->delete($user))->toBeFalse();
});
