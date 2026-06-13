<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Document\Policies\DocumentPolicy;
use App\User\Models\User;

beforeEach(function () {
    $this->policy = app(DocumentPolicy::class);
});

test('admin can create document', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->create($admin))->toBeTrue();
});

test('teacher cannot create document', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    expect($this->policy->create($teacher))->toBeFalse();
});

test('admin can view any document', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->view($admin, Document::factory()->create()))->toBeTrue();
});

test('user can view active document', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $document = Document::factory()->create(['is_active' => true]);

    expect($this->policy->view($user, $document))->toBeTrue();
});

test('admin can delete document', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->delete($admin, Document::factory()->create()))->toBeTrue();
});

test('teacher cannot delete document', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    expect($this->policy->delete($teacher, Document::factory()->create()))->toBeFalse();
});
