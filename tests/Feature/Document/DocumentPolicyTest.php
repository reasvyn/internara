<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Document\Models\Document;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('DocumentPolicy', function () {
    test('allows admin-group, teacher, and student on viewAny; denies supervisor', function () {
        foreach (['admin', 'teacher', 'student'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);
            expect(Gate::allows('viewAny', Document::class))->toBeTrue();
        }

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->actingAs($supervisor);
        expect(Gate::allows('viewAny', Document::class))->toBeFalse();
    });

    test('allows anyone on an active document, restricts inactive to admin', function () {
        $active = Document::factory()->create(['is_active' => true]);
        $inactive = Document::factory()->create(['is_active' => false]);

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('view', $active))->toBeTrue()
            ->and(Gate::allows('view', $inactive))->toBeFalse();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $inactive))->toBeTrue();
    });

    test('allows admin on create, update, and delete; denies student', function () {
        $document = Document::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('create', Document::class))->toBeTrue()
            ->and(Gate::allows('update', $document))->toBeTrue()
            ->and(Gate::allows('delete', $document))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', Document::class))->toBeFalse()
            ->and(Gate::allows('update', $document))->toBeFalse()
            ->and(Gate::allows('delete', $document))->toBeFalse();
    });

    test('allows superadmin via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $inactive = Document::factory()->create(['is_active' => false]);

        $this->actingAs($superadmin);

        expect(Gate::allows('view', $inactive))->toBeTrue()
            ->and(Gate::allows('delete', $inactive))->toBeTrue();
    });
});
