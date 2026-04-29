<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Modules\Setup\Models\Setup;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * [S1 - Secure] Test full wizard flow with security checks
 * [S2 - Sustain] Test clear step progression
 * [S3 - Scalable] Test UUID-based operations
 */
describe('Setup Wizard Flow', function () {
    beforeEach(function () {
        DB::table('setups')->truncate();
        DB::table('users')->truncate();
        DB::table('schools')->truncate();
        DB::table('departments')->truncate();
        DB::table('internships')->truncate();
    });

    it('completes full wizard flow with valid data', function () {
        $setupService = app(SetupService::class);
        $token = $setupService->generateToken();
        
        // Step 1: Welcome (just click next)
        $this->get("/setup/welcome?token={$token}")
            ->assertStatus(200);
        
        $setup = $setupService->getSetup();
        expect($setup->isStepCompleted('welcome'))->toBeTrue();
        
        // Step 2: School setup
        $response = $this->post("/setup/school?token={$token}", [
            'name' => 'Test University',
            'type' => 'university',
            'address' => '123 Test Street',
            'phone' => '123456789',
            'email' => 'school@test.com',
        ]);
        $response->assertRedirect("/setup/account?token={$token}");
        
        // Step 3: Account setup
        $response = $this->post("/setup/account?token={$token}", [
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ]);
        $response->assertRedirect("/setup/department?token={$token}");
        
        // Verify admin was created
        $admin = User::where('email', 'admin@test.com')->first();
        expect($admin)->not->toBeNull();
        expect(Hash::check('password123', $admin->password))->toBeTrue();
        
        // Step 4: Department setup
        $response = $this->post("/setup/department?token={$token}", [
            'name' => 'IT Department',
            'code' => 'IT',
            'description' => 'Information Technology Dept',
        ]);
        $response->assertRedirect("/setup/internship?token={$token}");
        
        // Step 5: Internship setup
        $response = $this->post("/setup/internship?token={$token}", [
            'name' => '2026 Internship',
            'startDate' => now()->addDays(7)->format('Y-m-d'),
            'endDate' => now()->addMonths(6)->format('Y-m-d'),
            'description' => 'Annual internship program',
        ]);
        $response->assertRedirect("/setup/complete?token={$token}");
        
        // Step 6: Complete setup
        $response = $this->post("/setup/complete?token={$token}", [
            'dataVerified' => '1',
            'securityAware' => '1',
            'legalAgreed' => '1',
        ]);
        $response->assertRedirect('/login');
        
        // Verify setup is finalized
        $setup = $setupService->getSetup();
        expect($setup->is_installed)->toBeTrue();
        expect($setup->setup_token_encrypted)->toBeNull();
        expect($setup->token_expires_at)->toBeNull();
    });

    it('prevents step bypassing without completing previous steps', function () {
        $setupService = app(SetupService::class);
        $token = $setupService->generateToken();
        
        // Try to access school step without completing welcome
        $response = $this->get("/setup/school?token={$token}");
        
        // Should redirect to welcome
        expect($response->getStatusCode())->toBe(302);
    });

    it('denies access with invalid token', function () {
        $response = $this->get('/setup/welcome?token=invalid-token');
        
        $response->assertRedirect('/setup/welcome');
        $response->assertSessionHasErrors('token');
    });

    it('denies access with expired token', function () {
        $setupService = app(SetupService::class);
        $token = $setupService->generateToken();
        
        $setup = $setupService->getSetup();
        $setup->token_expires_at = now()->subHour();
        $setup->save();
        
        $response = $this->get("/setup/welcome?token={$token}");
        
        $response->assertRedirect('/setup/welcome');
        $response->assertSessionHasErrors('token');
    });

    it('validates school form data', function () {
        $setupService = app(SetupService::class);
        $token = $setupService->generateToken();
        $setupService->completeStep('welcome');
        
        // Missing required fields
        $response = $this->post("/setup/school?token={$token}", []);
        $response->assertSessionHasErrors(['name', 'type', 'address', 'phone', 'email']);
        
        // Invalid email
        $response = $this->post("/setup/school?token={$token}", [
            'name' => 'Test',
            'type' => 'university',
            'address' => 'Test',
            'phone' => '123',
            'email' => 'invalid-email',
        ]);
        $response->assertSessionHasErrors(['email']);
    });

    it('validates account form data', function () {
        $setupService = app(SetupService::class);
        $token = $setupService->generateToken();
        $setupService->completeStep('welcome');
        $setupService->completeStep('school', ['school_id' => 'some-uuid']);
        
        // Password mismatch
        $response = $this->post("/setup/account?token={$token}", [
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'passwordConfirmation' => 'different',
        ]);
        $response->assertSessionHasErrors(['passwordConfirmation']);
        
        // Password too short
        $response = $this->post("/setup/account?token={$token}", [
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'short',
            'passwordConfirmation' => 'short',
        ]);
        $response->assertSessionHasErrors(['password']);
    });

    it('validates department form data', function () {
        $setupService = app(SetupService::class);
        $token = $setupService->generateToken();
        $setupService->completeStep('welcome');
        $setupService->completeStep('school', ['school_id' => 'some-uuid']);
        $setupService->completeStep('account', ['admin_id' => 'some-uuid']);
        
        // Missing required fields
        $response = $this->post("/setup/department?token={$token}", []);
        $response->assertSessionHasErrors(['name', 'code']);
    });

    it('validates internship form data', function () {
        $setupService = app(SetupService::class);
        $token = $setupService->generateToken();
        $setupService->completeStep('welcome');
        $setupService->completeStep('school', ['school_id' => 'some-uuid']);
        $setupService->completeStep('account', ['admin_id' => 'some-uuid']);
        $setupService->completeStep('department', ['department_id' => 'some-uuid']);
        
        // End date before start date
        $response = $this->post("/setup/internship?token={$token}", [
            'name' => 'Test',
            'startDate' => now()->addDays(30)->format('Y-m-d'),
            'endDate' => now()->addDays(10)->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors(['endDate']);
    });

    it('requires confirmation checkboxes on complete step', function () {
        $setupService = app(SetupService::class);
        $token = $setupService->generateToken();
        $setupService->completeStep('welcome');
        $setupService->completeStep('school', ['school_id' => 'some-uuid']);
        $setupService->completeStep('account', ['admin_id' => 'some-uuid']);
        $setupService->completeStep('department', ['department_id' => 'some-uuid']);
        $setupService->completeStep('internship', ['internship_id' => 'some-uuid']);
        
        $response = $this->post("/setup/complete?token={$token}", [
            'dataVerified' => '0',
            'securityAware' => '0',
            'legalAgreed' => '0',
        ]);
        $response->assertSessionHasErrors(['dataVerified', 'securityAware', 'legalAgreed']);
    });

    it('rate limits after 20 attempts', function () {
        for ($i = 0; $i < 20; $i++) {
            $this->get('/setup/welcome');
        }
        
        $response = $this->get('/setup/welcome');
        $response->assertStatus(429); // Too Many Requests
    });
});
