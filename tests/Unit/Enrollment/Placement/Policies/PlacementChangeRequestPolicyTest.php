<?php

declare(strict_types=1);

use App\Enrollment\Placement\Models\PlacementChangeRequest;
use App\Enrollment\Placement\Policies\PlacementChangeRequestPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {

    $this->policy = app(PlacementChangeRequestPolicy::class);
});

describe('viewAny', function () {
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        expect($this->policy->viewAny($user))->toBeTrue();
    });

    it('allows teacher', function () {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        expect($this->policy->viewAny($user))->toBeTrue();
    });

    it('allows student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        expect($this->policy->viewAny($user))->toBeTrue();
    });
});

describe('view', function () {
    it('allows admin to view any request', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $request = PlacementChangeRequest::factory()->create();

        expect($this->policy->view($user, $request))->toBeTrue();
    });

    it('allows owner student to view own request', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $request = PlacementChangeRequest::factory()->create(['requested_by' => $user->id]);

        expect($this->policy->view($user, $request))->toBeTrue();
    });

    it('denies unrelated student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $other = User::factory()->create();
        $request = PlacementChangeRequest::factory()->create(['requested_by' => $other->id]);

        expect($this->policy->view($user, $request))->toBeFalse();
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
});

describe('update', function () {
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $request = PlacementChangeRequest::factory()->create();

        expect($this->policy->update($user, $request))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $request = PlacementChangeRequest::factory()->create();

        expect($this->policy->update($user, $request))->toBeFalse();
    });
});

describe('delete', function () {
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $request = PlacementChangeRequest::factory()->create();

        expect($this->policy->delete($user, $request))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $request = PlacementChangeRequest::factory()->create();

        expect($this->policy->delete($user, $request))->toBeFalse();
    });
});
