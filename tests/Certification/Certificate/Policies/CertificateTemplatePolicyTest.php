<?php

declare(strict_types=1);

use App\Certification\Certificate\Models\CertificateTemplate;
use App\Certification\Certificate\Policies\CertificateTemplatePolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {

    $this->policy = app(CertificateTemplatePolicy::class);
    $this->template = CertificateTemplate::factory()->create();
});

test('admin can view any template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->viewAny($admin))->toBeTrue();
});

test('teacher cannot view templates', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    expect($this->policy->viewAny($teacher))->toBeFalse();
});

test('admin can create template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->create($admin))->toBeTrue();
});

test('admin can update template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->update($admin, $this->template))->toBeTrue();
});

test('admin can delete template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->delete($admin, $this->template))->toBeTrue();
});
