<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Actions\GetSupervisorDashboardStatsAction;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('GetSupervisorDashboardStatsAction', function () {
    it('returns zero counts when no data exists', function () {
        $user = User::factory()->create();
        $user->assignRole('supervisor');
        $this->actingAs($user);

        $stats = app(GetSupervisorDashboardStatsAction::class)->execute();

        expect($stats['activeInterns'])->toBe(0);
        expect($stats['pendingEvaluations'])->toBe(0);
        expect($stats['verifiedJournals'])->toBe(0);
    });
});
