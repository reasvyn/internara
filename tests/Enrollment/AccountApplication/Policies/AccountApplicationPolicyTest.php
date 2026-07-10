<?php

declare(strict_types=1);

use App\Enrollment\AccountApplication\Models\AccountApplication;
use App\Enrollment\AccountApplication\Policies\AccountApplicationPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {

    $this->policy = app(AccountApplicationPolicy::class);
});

describe('viewAny', function () {
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        expect($this->policy->viewAny($user))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        expect($this->policy->viewAny($user))->toBeFalse();
    });
});

describe('view', function () {
    it('allows admin to view any application', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $application = AccountApplication::factory()->create();

        expect($this->policy->view($user, $application))->toBeTrue();
    });

    it('allows user to view own application by email', function () {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $application = AccountApplication::factory()->create(['email' => 'owner@example.com']);

        expect($this->policy->view($user, $application))->toBeTrue();
    });

    it('denies user viewing others application', function () {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $application = AccountApplication::factory()->create(['email' => 'other@example.com']);

        expect($this->policy->view($user, $application))->toBeFalse();
    });
});

describe('create', function () {
    it('allows any authenticated user', function () {
        $user = User::factory()->create();

        expect($this->policy->create($user))->toBeTrue();
    });
});

describe('update', function () {
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $application = AccountApplication::factory()->create();

        expect($this->policy->update($user, $application))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $application = AccountApplication::factory()->create();

        expect($this->policy->update($user, $application))->toBeFalse();
    });
});

describe('delete', function () {
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $application = AccountApplication::factory()->create();

        expect($this->policy->delete($user, $application))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $application = AccountApplication::factory()->create();

        expect($this->policy->delete($user, $application))->toBeFalse();
    });
});
