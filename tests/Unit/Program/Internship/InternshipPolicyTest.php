<?php

declare(strict_types=1);

use App\Program\Internship\Models\Internship;
use App\Program\Internship\Policies\InternshipPolicy;
use App\User\Models\User;

beforeEach(function () {
    $this->policy = new InternshipPolicy;
});

test('super admin can view any internship', function () {
    $user = new class extends User
    {
        public function hasAnyRole(...$roles): bool
        {
            return true;
        }
    };

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('student can view any internship', function () {
    $user = new class extends User
    {
        public function hasAnyRole(...$roles): bool
        {
            return true;
        }
    };

    expect($this->policy->view($user, new Internship))->toBeTrue();
});

test('guest cannot view internships', function () {
    $user = new class extends User
    {
        public function hasAnyRole(...$roles): bool
        {
            return false;
        }
    };
    $user->id = 1;

    expect($this->policy->viewAny($user))->toBeFalse();
});

test('admin can create internship', function () {
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

test('non-admin cannot create internship', function () {
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

test('admin can update internship', function () {
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

    expect($this->policy->update($user, new Internship))->toBeTrue();
});

test('non-admin cannot update internship', function () {
    $user = new class extends User
    {
        public function hasAnyRole(...$roles): bool
        {
            return false;
        }
    };
    $user->id = 1;

    expect($this->policy->update($user, new Internship))->toBeFalse();
});

test('admin can delete internship without placements or registrations', function () {
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

    $internship = Internship::factory()->make();
    $internship->setRelation('placements', collect());
    $internship->setRelation('registrations', collect());

    expect($this->policy->delete($user, $internship))->toBeTrue();
});

test('admin cannot delete internship with placements', function () {
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

    $internship = Internship::factory()->make();
    $internship->setRelation('placements', collect([new stdClass]));
    $internship->setRelation('registrations', collect());

    expect($this->policy->delete($user, $internship))->toBeFalse();
});

test('super admin can force delete', function () {
    $user = new class extends User
    {
        public function hasRole($roles, ?string $guard = null): bool
        {
            return $roles === 'super_admin' || $roles === 'superadmin';
        }
    };
    $user->id = 1;

    expect($this->policy->forceDelete($user, new Internship))->toBeTrue();
});

test('non-super-admin cannot force delete', function () {
    $user = new class extends User
    {
        public function hasRole($roles, ?string $guard = null): bool
        {
            return false;
        }
    };
    $user->id = 1;

    expect($this->policy->forceDelete($user, new Internship))->toBeFalse();
});
