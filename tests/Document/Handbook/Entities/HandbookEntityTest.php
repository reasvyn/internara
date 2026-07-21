<?php

declare(strict_types=1);

use App\Document\Handbook\Entities\HandbookEntity;
use App\Document\Handbook\Enums\HandbookAudience;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->student = User::factory()->create();
    $this->student->assignRole('student');

    $this->teacher = User::factory()->create();
    $this->teacher->assignRole('teacher');
});

test('handbook is targeted at student audience', function () {
    $entity = new HandbookEntity(
        id: '123',
        title: 'Student Code of Conduct',
        version: 1,
        isActive: true,
        audience: HandbookAudience::STUDENT,
        description: null,
        hasFile: true,
        createdAt: null,
    );

    expect($entity->isTargetedAt($this->student))->toBeTrue();
    expect($entity->isTargetedAt($this->teacher))->toBeFalse();
});

test('handbook with all audience targets everyone', function () {
    $entity = new HandbookEntity(
        id: '456',
        title: 'General Policy',
        version: 1,
        isActive: true,
        audience: HandbookAudience::ALL,
        description: null,
        hasFile: true,
        createdAt: null,
    );

    expect($entity->isTargetedAt($this->student))->toBeTrue();
    expect($entity->isTargetedAt($this->teacher))->toBeTrue();
});

test('handbook is available only when active and has file', function () {
    $activeWithFile = new HandbookEntity(
        id: '1', title: 'Active', version: 1, isActive: true,
        audience: HandbookAudience::ALL, description: null, hasFile: true, createdAt: null,
    );
    expect($activeWithFile->isAvailable())->toBeTrue();

    $inactiveWithFile = new HandbookEntity(
        id: '2', title: 'Inactive', version: 1, isActive: false,
        audience: HandbookAudience::ALL, description: null, hasFile: true, createdAt: null,
    );
    expect($inactiveWithFile->isAvailable())->toBeFalse();

    $activeNoFile = new HandbookEntity(
        id: '3', title: 'No File', version: 1, isActive: true,
        audience: HandbookAudience::ALL, description: null, hasFile: false, createdAt: null,
    );
    expect($activeNoFile->isAvailable())->toBeFalse();
});

test('handbook can always be deleted', function () {
    $entity = new HandbookEntity(
        id: '1', title: 'Test', version: 1, isActive: true,
        audience: HandbookAudience::ALL, description: null, hasFile: true, createdAt: null,
    );

    expect($entity->canBeDeleted())->toBeTrue();
});

test('handbook is newer than null acknowledgment', function () {
    $entity = new HandbookEntity(
        id: '1', title: 'Test', version: 2, isActive: true,
        audience: HandbookAudience::ALL, description: null, hasFile: true, createdAt: null,
    );

    expect($entity->isNewerThan(null))->toBeTrue();
});
