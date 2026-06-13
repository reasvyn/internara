<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\RegistrationDocument;
use App\Enrollment\Registration\Policies\RegistrationDocumentPolicy;
use App\User\Models\User;

beforeEach(function () {
    $this->policy = app(RegistrationDocumentPolicy::class);
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
    it('allows admin to view any document', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $document = RegistrationDocument::factory()->create();

        expect($this->policy->view($user, $document))->toBeTrue();
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
        $document = RegistrationDocument::factory()->create();

        expect($this->policy->update($user, $document))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $document = RegistrationDocument::factory()->create();

        expect($this->policy->update($user, $document))->toBeFalse();
    });
});

describe('delete', function () {
    it('allows admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $document = RegistrationDocument::factory()->create();

        expect($this->policy->delete($user, $document))->toBeTrue();
    });

    it('denies student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $document = RegistrationDocument::factory()->create();

        expect($this->policy->delete($user, $document))->toBeFalse();
    });
});
