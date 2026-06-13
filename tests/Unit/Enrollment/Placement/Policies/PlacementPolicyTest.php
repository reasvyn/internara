<?php

declare(strict_types=1);

use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Placement\Policies\PlacementPolicy;
use App\User\Models\User;

beforeEach(function () {
    $this->policy = app(PlacementPolicy::class);
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
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $placement = Placement::factory()->create();

        expect($this->policy->view($user, $placement))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $placement = Placement::factory()->create();

        expect($this->policy->view($user, $placement))->toBeFalse();
    });
});

describe('create', function () {
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        expect($this->policy->create($user))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        expect($this->policy->create($user))->toBeFalse();
    });
});

describe('update', function () {
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $placement = Placement::factory()->create();

        expect($this->policy->update($user, $placement))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $placement = Placement::factory()->create();

        expect($this->policy->update($user, $placement))->toBeFalse();
    });
});

describe('delete', function () {
    it('allows admin when placement has no direct placements', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $placement = Placement::factory()->create();

        expect($this->policy->delete($user, $placement))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $placement = Placement::factory()->create();

        expect($this->policy->delete($user, $placement))->toBeFalse();
    });
});
