<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin;

use App\Modules\Auth\Domain\Permission\Enums\Role;
use App\Modules\SysAdmin\Domain\Observability\Services\PulseGuard;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('E1MSJ: Pulse Dashboard Access Guard', function (): void {
    test('E1MSJ-FR-SYS-035: returns false when user is unauthenticated', function (): void {
        expect(PulseGuard::viewPulse(null))->toBeFalse();
    });

    test('E1MSJ-FR-SYS-035: permits super_admin and admin users', function (): void {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::SUPER_ADMIN);

        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN);

        expect(PulseGuard::viewPulse($superAdmin))->toBeTrue()
            ->and(PulseGuard::viewPulse($admin))->toBeTrue();
    });

    test('E1MSJ-FR-SYS-035: denies non-admin roles (student, teacher, supervisor)', function (): void {
        $student = User::factory()->create();
        $student->assignRole(Role::STUDENT);

        $teacher = User::factory()->create();
        $teacher->assignRole(Role::TEACHER);

        $supervisor = User::factory()->create();
        $supervisor->assignRole(Role::SUPERVISOR);

        expect(PulseGuard::viewPulse($student))->toBeFalse()
            ->and(PulseGuard::viewPulse($teacher))->toBeFalse()
            ->and(PulseGuard::viewPulse($supervisor))->toBeFalse();
    });
});
