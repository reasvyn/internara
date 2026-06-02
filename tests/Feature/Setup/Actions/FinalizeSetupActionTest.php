<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Domain\Setup\Actions\FinalizeSetupAction;
use App\Domain\Setup\Events\SetupFinalized;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use RuntimeException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
    Session::flush();
    Event::fake();
    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
});

describe('FinalizeSetupAction', function () {
    it('completes setup and returns recovery key', function () {
        $result = app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'SMK Final Test',
                'institutional_code' => 'SFT',
                'email' => 'final@test.sch.id',
            ],
            departmentData: [
                'name' => 'Teknik Otomotif',
            ],
            adminData: [
                'email' => 'admin@final.test',
                'password' => 'SecurePass123!',
            ],
        );

        expect($result)->toBeString()
            ->and(strlen($result))->toBe(64);
    });

    it('creates school, department, and admin records', function () {
        app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'SMK Final',
                'institutional_code' => 'SMC',
                'email' => 'final@smk.test',
            ],
            departmentData: [
                'name' => 'Akuntansi',
            ],
            adminData: [
                'email' => 'created@final.test',
                'password' => 'SecurePass123!',
            ],
        );

        $setup = Setup::first();
        expect($setup->is_installed)->toBeTrue()
            ->and($setup->school)->not->toBeNull()
            ->and($setup->department)->not->toBeNull();

        $admin = User::where('email', 'created@final.test')->first();
        expect($admin)->not->toBeNull();
    });

    it('marks setup as installed', function () {
        app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'Installed School',
                'institutional_code' => 'INS',
                'email' => 'ins@test.sch.id',
            ],
            departmentData: ['name' => 'Umum'],
            adminData: [
                'email' => 'installed@test.com',
                'password' => 'SecurePass123!',
            ],
        );

        $setup = Setup::first();
        expect($setup->is_installed)->toBeTrue();
    });

    it('throws if system is already installed', function () {
        Setup::factory()->installed()->create();

        app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'Test',
                'institutional_code' => 'TST',
                'email' => 'test@test.sch.id',
            ],
            departmentData: ['name' => 'Test'],
            adminData: [
                'email' => 'dup@test.com',
                'password' => 'SecurePass123!',
            ],
        );
    })->throws(RuntimeException::class, 'System is already installed.');

    it('dispatches SetupFinalized event', function () {
        app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'Event School',
                'institutional_code' => 'EVT',
                'email' => 'event@test.sch.id',
            ],
            departmentData: ['name' => 'Event Dept'],
            adminData: [
                'email' => 'event@test.com',
                'password' => 'SecurePass123!',
            ],
        );

        Event::assertDispatched(SetupFinalized::class);
    });

    it('clears session data after completion', function () {
        Session::put('setup.authorized', true);
        Session::put('setup.token', 'test');
        Session::put('setup.form_data', ['school' => ['name' => 'test']]);

        app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'Session School',
                'institutional_code' => 'SSN',
                'email' => 'session@test.sch.id',
            ],
            departmentData: ['name' => 'Session Dept'],
            adminData: [
                'email' => 'session@test.com',
                'password' => 'SecurePass123!',
            ],
        );

        expect(Session::has('setup.authorized'))->toBeFalse()
            ->and(Session::has('setup.token'))->toBeFalse()
            ->and(Session::has('setup.form_data'))->toBeFalse();
    });

    it('stores the recovery key hashed in database', function () {
        $plaintext = app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'Recovery School',
                'institutional_code' => 'RCV',
                'email' => 'recovery@test.sch.id',
            ],
            departmentData: ['name' => 'Recovery Dept'],
            adminData: [
                'email' => 'recovery@test.com',
                'password' => 'SecurePass123!',
            ],
        );

        $setup = Setup::first();
        expect($setup->recovery_key)->not->toBeNull();
        expect(Hash::check($plaintext, $setup->recovery_key))->toBeTrue();
    });

    it('clears the setup token after finalization', function () {
        $setup = Setup::factory()->withToken()->create();

        app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'Clear Token School',
                'institutional_code' => 'CLR',
                'email' => 'clear@test.sch.id',
            ],
            departmentData: ['name' => 'Clear Dept'],
            adminData: [
                'email' => 'clear@test.com',
                'password' => 'SecurePass123!',
            ],
        );

        $setup->refresh();
        expect($setup->setup_token)->toBeNull()
            ->and($setup->token_expires_at)->toBeNull();
    });

    it('marks all provided steps as completed', function () {
        app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'Step School',
                'institutional_code' => 'STP',
                'email' => 'step@test.sch.id',
            ],
            departmentData: ['name' => 'Step Dept'],
            adminData: [
                'email' => 'step@test.com',
                'password' => 'SecurePass123!',
            ],
            stepsToComplete: ['school', 'department', 'account', 'internship'],
        );

        $setup = Setup::first();
        expect($setup->completed_steps)->toContain('school', 'department', 'account');
    });
});
