<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Enrollment\Registration\Policies\RegistrationPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {

    $this->policy = app(RegistrationPolicy::class);
});

describe('viewAny', function () {
    it('allows super_admin', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        expect($this->policy->viewAny($user))->toBeTrue();
    });

    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        expect($this->policy->viewAny($user))->toBeTrue();
    });

    it('allows student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        expect($this->policy->viewAny($user))->toBeTrue();
    });

    it('denies unauthorized role', function () {
        $user = User::factory()->create();

        expect($this->policy->viewAny($user))->toBeFalse();
    });
});

describe('view', function () {
    it('allows admin to view any registration', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $registration = Registration::factory()->create();

        expect($this->policy->view($user, $registration))->toBeTrue();
    });

    it('denies unrelated student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $other = User::factory()->create();
        $registration = Registration::factory()->create(['student_id' => $other->id]);

        expect($this->policy->view($user, $registration))->toBeFalse();
    });
});

describe('create', function () {
    it('allows student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        expect($this->policy->create($user))->toBeTrue();
    });

    it('denies admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        expect($this->policy->create($user))->toBeFalse();
    });

    it('denies teacher', function () {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        expect($this->policy->create($user))->toBeFalse();
    });
});

describe('update', function () {
    it('allows admin on any registration', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $registration = Registration::factory()->create();

        expect($this->policy->update($user, $registration))->toBeTrue();
    });

    it('denies unrelated student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $other = User::factory()->create();
        $registration = Registration::factory()->create(['student_id' => $other->id, 'status' => 'pending']);

        expect($this->policy->update($user, $registration))->toBeFalse();
    });
});

describe('approve', function () {
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $registration = Registration::factory()->create();

        expect($this->policy->approve($user, $registration))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $registration = Registration::factory()->create();

        expect($this->policy->approve($user, $registration))->toBeFalse();
    });
});

describe('delete', function () {
    it('allows admin on any registration', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $registration = Registration::factory()->create();

        expect($this->policy->delete($user, $registration))->toBeTrue();
    });

    it('denies unrelated student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $other = User::factory()->create();
        $registration = Registration::factory()->create(['student_id' => $other->id, 'status' => 'pending']);

        expect($this->policy->delete($user, $registration))->toBeFalse();
    });
});
