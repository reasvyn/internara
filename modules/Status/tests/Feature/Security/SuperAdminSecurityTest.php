<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Status\Enums\AccountStatus;
use Modules\Status\Services\SuperAdminGuardRails;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * SuperAdminSecurityTest
 *
 * Tests enterprise-grade Super Admin protection:
 * - Immutability enforcement
 * - Session isolation
 * - Minimum requirement enforcement
 * - Activity tracking
 */
class SuperAdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    private SuperAdminGuardRails $guards;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guards = app(SuperAdminGuardRails::class);
    }

    /** @test */
    public function prevents_super_admin_status_change()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $superAdmin->update(['account_status' => AccountStatus::PROTECTED->value]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('immutable');

        $this->guards->canChangeStatus($superAdmin, AccountStatus::VERIFIED);
    }

    /** @test */
    public function prevents_deactivating_last_super_admin()
    {
        $lastSuperAdmin = User::factory()->create();
        $lastSuperAdmin->assignRole('super_admin');
        $lastSuperAdmin->update(['account_status' => AccountStatus::PROTECTED->value]);

        // Ensure no other Super Admins exist
        User::where('id', '!=', $lastSuperAdmin->id)->update([
            'account_status' => AccountStatus::VERIFIED->value,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('at least one Super Admin');

        $this->guards->canDeactivate($lastSuperAdmin);
    }

    /** @test */
    public function enforces_session_isolation_for_super_admin()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $superAdmin->update(['account_status' => AccountStatus::PROTECTED->value]);

        $this->actingAs($superAdmin);

        $result = $this->guards->enforceSessionIsolation($superAdmin);

        $this->assertTrue($result);

        // Verify session is cached
        $cached = cache()->get("user_sessions_{$superAdmin->id}");
        $this->assertNotNull($cached);
        $this->assertIsArray($cached);
    }

    /** @test */
    public function tracks_super_admin_activity()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $superAdmin->update(['account_status' => AccountStatus::PROTECTED->value]);

        $this->actingAs($superAdmin);

        $this->guards->trackActivity($superAdmin, 'login', ['ip' => '127.0.0.1']);

        // Verify activity logged
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $superAdmin->id,
            'event' => 'super_admin_login',
        ]);
    }

    /** @test */
    public function blocks_super_admin_from_unauthorized_ip()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $superAdmin->update(['account_status' => AccountStatus::PROTECTED->value]);

        // Configure IP whitelist
        config(['auth.super_admin.ip_whitelist' => '192.168.1.0/24']);

        $this->actingAs($superAdmin);

        // Mock request IP
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5']);

        $allowed = $this->guards->isIpAllowed($superAdmin);

        $this->assertFalse($allowed);
    }

    /** @test */
    public function allows_super_admin_from_whitelisted_ip()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $superAdmin->update(['account_status' => AccountStatus::PROTECTED->value]);

        // Configure IP whitelist
        config(['auth.super_admin.ip_whitelist' => '127.0.0.0/8']);

        $this->actingAs($superAdmin);

        $allowed = $this->guards->isIpAllowed($superAdmin);

        $this->assertTrue($allowed);
    }

    /** @test */
    public function prevents_super_admin_self_approval()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $superAdmin->update(['account_status' => AccountStatus::PROTECTED->value]);

        $this->actingAs($superAdmin);

        // Create approval request
        $this->guards->requiresDualApproval($superAdmin, 'password', [
            'password' => 'NewPassword123!',
        ]);

        $approval = DB::table('super_admin_approvals')->first();

        // Self-approval should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot approve your own changes');

        $this->guards->approveChange($approval->id, $superAdmin);
    }

    /** @test */
    public function can_approve_super_admin_change_with_dual_approval()
    {
        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole('super_admin');
        $superAdmin1->update(['account_status' => AccountStatus::PROTECTED->value]);

        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');
        $superAdmin2->update(['account_status' => AccountStatus::PROTECTED->value]);

        $this->actingAs($superAdmin1);

        // Request change
        $this->guards->requiresDualApproval($superAdmin1, 'password', [
            'password' => 'NewPassword123!',
        ]);

        $approval = DB::table('super_admin_approvals')->first();

        // Approve as second Super Admin
        $this->actingAs($superAdmin2);
        $approved = $this->guards->approveChange($approval->id, $superAdmin2);

        $this->assertTrue($approved);
        $this->assertDatabaseHas('super_admin_approvals', [
            'id' => $approval->id,
            'status' => 'approved',
        ]);
    }
}
