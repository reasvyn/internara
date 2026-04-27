<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Status\Enums\AccountStatus;
use Modules\Status\Livewire\QuickActionButtons;
use Modules\Status\Livewire\StatusSelector;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * StatusSelectorComponentTest
 *
 * Tests Livewire StatusSelector component:
 * - Rendering available transitions
 * - Applying status changes
 * - Authorization checks
 * - Error handling
 */
class StatusSelectorComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function renders_status_selector()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::ACTIVATED->value,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Livewire::test(StatusSelector::class, ['userId' => $user->id])->assertStatus(200);
    }

    /** @test */
    public function displays_available_transitions()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::ACTIVATED->value,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Livewire::test(StatusSelector::class, ['userId' => $user->id])
            ->assertSee('VERIFIED')
            ->assertSee('RESTRICTED')
            ->assertSee('SUSPENDED');
    }

    /** @test */
    public function can_transition_status()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::ACTIVATED->value,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Livewire::test(StatusSelector::class, ['userId' => $user->id])
            ->call('transitionStatus', AccountStatus::VERIFIED->value, 'Admin verification')
            ->assertDispatched('statusChanged');

        $this->assertTrue($user->refresh()->account_status === AccountStatus::VERIFIED->value);
    }

    /** @test */
    public function prevents_unauthorized_transitions()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::ACTIVATED->value,
        ]);

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($student);

        Livewire::test(StatusSelector::class, ['userId' => $user->id])
            ->call('transitionStatus', AccountStatus::VERIFIED->value, 'Unauthorized attempt')
            ->assertForbidden();
    }
}

/**
 * QuickActionButtonsComponentTest
 *
 * Tests QuickActionButtons Livewire component:
 * - One-click actions (verify, suspend, unlock)
 * - Button availability by status
 * - Authorization enforcement
 */
class QuickActionButtonsComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function renders_quick_action_buttons()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::ACTIVATED->value,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Livewire::test(QuickActionButtons::class, ['userId' => $user->id])
            ->assertStatus(200)
            ->assertSee('Verify')
            ->assertSee('Suspend');
    }

    /** @test */
    public function can_verify_account_with_quick_action()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::ACTIVATED->value,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Livewire::test(QuickActionButtons::class, ['userId' => $user->id])
            ->call('verify')
            ->assertDispatched('accountVerified');

        $this->assertTrue($user->refresh()->account_status === AccountStatus::VERIFIED->value);
    }

    /** @test */
    public function can_suspend_account_with_quick_action()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::VERIFIED->value,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Livewire::test(QuickActionButtons::class, ['userId' => $user->id])
            ->call('suspend')
            ->assertDispatched('accountSuspended');

        $this->assertTrue($user->refresh()->account_status === AccountStatus::SUSPENDED->value);
    }

    /** @test */
    public function hides_buttons_for_protected_accounts()
    {
        $superAdmin = User::factory()->create([
            'account_status' => AccountStatus::PROTECTED->value,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Livewire::test(QuickActionButtons::class, ['userId' => $superAdmin->id])
            ->assertDontSee('Suspend')
            ->assertDontSee('Verify');
    }
}
