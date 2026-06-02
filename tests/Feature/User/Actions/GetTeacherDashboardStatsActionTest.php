<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Actions\GetTeacherDashboardStatsAction;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('GetTeacherDashboardStatsAction', function () {
    it('returns zero counts when no data exists', function () {
        $user = User::factory()->create();
        $user->assignRole('teacher');
        $this->actingAs($user);

        $stats = app(GetTeacherDashboardStatsAction::class)->execute();

        expect($stats['supervisedStudents'])->toBe(0);
        expect($stats['pendingJournals'])->toBe(0);
        expect($stats['activeCompanies'])->toBe(0);
    });
});
