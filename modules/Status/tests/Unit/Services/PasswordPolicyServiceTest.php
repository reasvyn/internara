<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Unit\Services;

use Modules\Status\Services\PasswordPolicyService;
use Modules\User\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * PasswordPolicyServiceTest
 *
 * Tests password policy enforcement:
 * - Complexity requirements
 * - Expiration by role
 * - History tracking
 * - Password changes
 */
class PasswordPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    private PasswordPolicyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PasswordPolicyService::class);
    }

    /** @test */
    public function validates_password_complexity()
    {
        $weak = 'weak';
        $result = $this->service->validatePasswordComplexity($weak);
        $this->assertFalse($result['valid']);
        $this->assertCount(5, $result['errors']); // All requirements missing

        $strong = 'SecureP@ssw0rd';
        $result = $this->service->validatePasswordComplexity($strong);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function requires_uppercase_letter()
    {
        $password = 'securep@ssw0rd'; // lowercase only
        $result = $this->service->validatePasswordComplexity($password);
        $this->assertFalse($result['valid']);
        $this->assertContains('uppercase', $result['errors'][0]);
    }

    /** @test */
    public function requires_special_character()
    {
        $password = 'SecurePassword0'; // no special char
        $result = $this->service->validatePasswordComplexity($password);
        $this->assertFalse($result['valid']);
        $this->assertContains('special character', implode('', $result['errors']));
    }

    /** @test */
    public function super_admin_password_expires_30_days()
    {
        $user = User::factory()->create([
            'password_changed_at' => now()->subDays(31),
        ]);
        $user->assignRole('super_admin');

        $this->assertTrue($this->service->isExpired($user));
    }

    /** @test */
    public function admin_password_expires_60_days()
    {
        $user = User::factory()->create([
            'password_changed_at' => now()->subDays(61),
        ]);
        $user->assignRole('admin');

        $this->assertTrue($this->service->isExpired($user));
    }

    /** @test */
    public function standard_user_password_expires_90_days()
    {
        $user = User::factory()->create([
            'password_changed_at' => now()->subDays(91),
        ]);
        $user->assignRole('student');

        $this->assertTrue($this->service->isExpired($user));
    }

    /** @test */
    public function detects_expiration_warning_period()
    {
        $user = User::factory()->create([
            'password_changed_at' => now()->subDays(77), // 13 days before 90-day expiry
        ]);
        $user->assignRole('student');

        $this->assertTrue($this->service->isExpiringSoon($user));
    }

    /** @test */
    public function prevents_password_reuse()
    {
        $user = User::factory()->create([
            'password' => Hash::make('SecurePassword123!'),
        ]);

        // Create password history
        $this->service->updatePassword($user, 'NewPassword456!');
        
        $oldPassword = 'SecurePassword123!';
        $canReuse = $this->service->validatePasswordHistory($user, $oldPassword);
        
        $this->assertFalse($canReuse); // Cannot reuse old password
    }

    /** @test */
    public function allows_new_unique_passwords()
    {
        $user = User::factory()->create();

        $newPassword = 'CompletelyNewPassword789!';
        $canUse = $this->service->validatePasswordHistory($user, $newPassword);
        
        $this->assertTrue($canUse); // New password allowed
    }

    /** @test */
    public function updates_password_and_records_history()
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $oldHash = $user->password;

        $this->service->updatePassword($user, 'NewPassword456!');

        // Verify password changed
        $this->assertNotEquals($oldHash, $user->refresh()->password);

        // Verify history recorded
        $this->assertDatabaseHas('password_history', [
            'user_id' => $user->id,
            'password_hash' => $oldHash,
        ]);
    }

    /** @test */
    public function requires_password_reset_on_next_login()
    {
        $user = User::factory()->create();

        $this->service->requirePasswordReset($user, 'admin_required');

        $this->assertTrue($this->service->mustResetPassword($user->refresh()));
    }

    /** @test */
    public function calculates_days_until_expiry()
    {
        $user = User::factory()->create([
            'password_changed_at' => now()->subDays(80),
        ]);
        $user->assignRole('student'); // 90 day expiry

        $daysRemaining = $this->service->getDaysUntilExpiry($user);
        
        $this->assertGreaterThanOrEqual(9, $daysRemaining);
        $this->assertLessThanOrEqual(11, $daysRemaining);
    }
}
