<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use App\Modules\User\Domain\UserManagement\Actions\ToggleUserStatusAction;
use App\Modules\User\Domain\UserManagement\Livewire\UserManager;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('95EVB: ToggleUserStatusAction', function () {
    beforeEach(function () {
        Notification::fake();
        Event::fake();
    });

    test('95EVB-FR-AS9: toggles VERIFIED to SUSPENDED', function () {
        $admin = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $admin->assignRole('admin');
        $target = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $target->assignRole('student');

        $this->actingAs($admin);

        $action = app(ToggleUserStatusAction::class);
        $result = $action->execute($target);

        expect($result->status)->toBe(AccountStatus::SUSPENDED)
            ->and(User::find($target->id)->status)->toBe(AccountStatus::SUSPENDED);
    });

    test('95EVB-FR-AS9: toggles SUSPENDED to VERIFIED', function () {
        $admin = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $admin->assignRole('admin');
        $target = User::factory()->create(['status' => AccountStatus::SUSPENDED->value]);
        $target->assignRole('student');

        $this->actingAs($admin);

        $action = app(ToggleUserStatusAction::class);
        $result = $action->execute($target);

        expect($result->status)->toBe(AccountStatus::VERIFIED)
            ->and(User::find($target->id)->status)->toBe(AccountStatus::VERIFIED);
    });

    test('95EVB-FR-SAP8: rejects toggle on super admin', function () {
        $admin = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $admin->assignRole('admin');
        $superAdmin = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $superAdmin->assignRole('superadmin');

        $this->actingAs($admin);

        $action = app(ToggleUserStatusAction::class);

        expect(fn () => $action->execute($superAdmin))->toThrow(RejectedException::class);
    });

    test('95EVB-NFR-S5: rejects self-status-change', function () {
        $admin = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $action = app(ToggleUserStatusAction::class);

        expect(fn () => $action->execute($admin))->toThrow(RejectedException::class);
    });

    test('95EVB-FR-AS9: Livewire UserManager wires toggleStatus', function () {
        $admin = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $admin->assignRole('admin');
        $target = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $target->assignRole('student');

        $this->actingAs($admin);

        Livewire::test(UserManager::class)
            ->call('toggleStatus', $target->id);

        expect(User::find($target->id)->status)->toBe(AccountStatus::SUSPENDED);
    });
});
