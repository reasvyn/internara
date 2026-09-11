<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\User\Domain\Notifications\Models\Notification;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('TXR2H: NotificationPolicy', function () {
    test('TXR2H-FR-NOTIF-020: viewAny open to any authenticated user', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', Notification::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('viewAny', Notification::class))->toBeTrue();
    });

    test('TXR2H-FR-NOTIF-020: owner can view and update, outsider cannot', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $notification = Notification::factory()->create(['user_id' => $owner->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $this->actingAs($owner);
        expect(Gate::allows('view', $notification))->toBeTrue()
            ->and(Gate::allows('update', $notification))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $notification))->toBeFalse()
            ->and(Gate::allows('update', $notification))->toBeFalse();
    });

    test('TXR2H-FR-NOTIF-020: create and delete reserved to admin; owner cannot delete own', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $notification = Notification::factory()->create(['user_id' => $owner->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('create', Notification::class))->toBeTrue()
            ->and(Gate::allows('delete', $notification))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('create', Notification::class))->toBeFalse()
            ->and(Gate::allows('delete', $notification))->toBeFalse();
    });

    test('T4B26-FR-RBAC-010: superadmin bypasses via before()', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $notification = Notification::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('view', $notification))->toBeTrue()
            ->and(Gate::allows('delete', $notification))->toBeTrue();
    });
});
