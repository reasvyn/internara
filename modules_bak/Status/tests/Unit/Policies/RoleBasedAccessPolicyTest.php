<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Unit\Policies;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Status\Enums\AccountStatus;
use Modules\Status\Policies\RoleBasedAccessPolicy;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * RoleBasedAccessPolicyTest
 *
 * Tests role-based authorization matrix:
 * - Role hierarchy (Super Admin > Admin > Supervisor > Teacher > Student)
 * - Permission gates
 * - Self-service restrictions
 * - Cross-role access prevention
 */
class RoleBasedAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    private RoleBasedAccessPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(RoleBasedAccessPolicy::class);
    }

    /** @test */
    public function super_admin_can_view_any_user()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $target = User::factory()->create();
        $target->assignRole('student');

        $this->assertTrue($this->policy->view($superAdmin, $target));
    }

    /** @test */
    public function admin_cannot_view_super_admin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertFalse($this->policy->view($admin, $superAdmin));
    }

    /** @test */
    public function supervisor_can_only_view_students()
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $student = User::factory()->create();
        $student->assignRole('student');

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $this->assertTrue($this->policy->view($supervisor, $student));
        $this->assertFalse($this->policy->view($supervisor, $teacher));
    }

    /** @test */
    public function can_always_view_own_account()
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->view($user, $user));
    }

    /** @test */
    public function super_admin_can_change_any_status()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $target = User::factory()->create();
        $target->assignRole('student');

        $this->assertTrue(
            $this->policy->changeStatus($superAdmin, $target, AccountStatus::SUSPENDED),
        );
    }

    /** @test */
    public function super_admin_cannot_downgrade_other_super_admins()
    {
        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole('super_admin');

        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');

        $this->assertFalse(
            $this->policy->changeStatus($superAdmin1, $superAdmin2, AccountStatus::VERIFIED),
        );
    }

    /** @test */
    public function admin_can_verify_students_only()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $student = User::factory()->create();
        $student->assignRole('student');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('admin');

        $this->assertTrue($this->policy->verify($admin, $student));
        $this->assertFalse($this->policy->verify($admin, $otherAdmin));
    }

    /** @test */
    public function supervisor_can_suspend_students()
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->assertTrue($this->policy->suspend($supervisor, $student));
    }

    /** @test */
    public function supervisor_cannot_suspend_other_roles()
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $this->assertFalse($this->policy->suspend($supervisor, $teacher));
    }

    /** @test */
    public function users_cannot_suspend_self()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertFalse($this->policy->suspend($user, $user));
    }

    /** @test */
    public function only_super_admin_can_permanently_delete()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create();
        $target->assignRole('student');

        $this->assertTrue($this->policy->delete($superAdmin, $target));
        $this->assertFalse($this->policy->delete($admin, $target));
    }

    /** @test */
    public function super_admin_accounts_cannot_be_deleted()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $otherSuperAdmin = User::factory()->create();
        $otherSuperAdmin->assignRole('super_admin');

        $this->assertFalse($this->policy->delete($superAdmin, $otherSuperAdmin));
    }

    /** @test */
    public function users_cannot_export_others_data_unless_authorized()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        // Student can export own data
        $this->assertTrue($this->policy->exportData($student, $student));

        // But cannot export other students' data
        $this->assertFalse($this->policy->exportData($student, $otherStudent));
    }

    /** @test */
    public function admin_can_export_any_user_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create();
        $target->assignRole('student');

        $this->assertTrue($this->policy->exportData($admin, $target));
    }
}
