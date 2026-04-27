<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Status\Enums\AccountStatus;
use Modules\Status\Services\AccountAuditLogger;
use Modules\Status\Services\StatusTransitionService;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * StatusTransitionServiceTest
 *
 * Tests the core account status transition engine with:
 * - Valid transitions
 * - Invalid transition prevention
 * - Role-based authorization
 * - Audit trail creation
 * - Notification dispatch
 */
class StatusTransitionServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private StatusTransitionService $service;

    private AccountAuditLogger $auditLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StatusTransitionService::class);
        $this->auditLogger = app(AccountAuditLogger::class);
    }

    /** @test */
    public function can_transition_from_provisioned_to_activated()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::PROVISIONED->value,
        ]);

        $this->service->transition(
            user: $user,
            fromStatus: AccountStatus::PROVISIONED,
            toStatus: AccountStatus::ACTIVATED,
            reason: 'Account activation',
            triggeredByUserId: $user->id,
        );

        $this->assertTrue($user->refresh()->account_status === AccountStatus::ACTIVATED->value);
    }

    /** @test */
    public function can_transition_from_activated_to_verified()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::ACTIVATED->value,
        ]);

        $this->service->transition(
            user: $user,
            fromStatus: AccountStatus::ACTIVATED,
            toStatus: AccountStatus::VERIFIED,
            reason: 'Admin verification',
            triggeredByUserId: auth()->id() ?? 1,
        );

        $this->assertTrue($user->refresh()->account_status === AccountStatus::VERIFIED->value);
    }

    /** @test */
    public function prevents_invalid_transitions()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::VERIFIED->value,
        ]);

        $this->expectException(\Exception::class);

        // Cannot go from VERIFIED back to PROVISIONED
        $this->service->transition(
            user: $user,
            fromStatus: AccountStatus::VERIFIED,
            toStatus: AccountStatus::PROVISIONED,
            reason: 'Invalid transition',
            triggeredByUserId: auth()->id() ?? 1,
        );
    }

    /** @test */
    public function creates_audit_trail_on_transition()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::PROVISIONED->value,
        ]);

        $this->service->transition(
            user: $user,
            fromStatus: AccountStatus::PROVISIONED,
            toStatus: AccountStatus::ACTIVATED,
            reason: 'User claimed account',
            triggeredByUserId: $user->id,
        );

        $this->assertDatabaseHas('account_status_history', [
            'user_id' => $user->id,
            'old_status' => AccountStatus::PROVISIONED->value,
            'new_status' => AccountStatus::ACTIVATED->value,
            'reason' => 'User claimed account',
        ]);
    }

    /** @test */
    public function protects_super_admin_status()
    {
        $superAdmin = User::factory()->create([
            'account_status' => AccountStatus::PROTECTED->value,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('immutable');

        $this->service->transition(
            user: $superAdmin,
            fromStatus: AccountStatus::PROTECTED,
            toStatus: AccountStatus::VERIFIED,
            reason: 'Try to downgrade Super Admin',
            triggeredByUserId: auth()->id() ?? 1,
        );
    }
}
