<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Modules\Setup\Models\Setup;
use Modules\Setup\Services\SetupService;

/**
 * [S1 - Secure] Test encrypted token storage
 * [S2 - Sustain] Test clear business logic
 * [S3 - Scalable] Test UUID-based operations
 */
describe('SetupService', function () {
    beforeEach(function () {
        DB::table('setups')->truncate();
    });

    describe('getSetup', function () {
        it('creates setup record if not exists', function () {
            $service = app(SetupService::class);

            $setup = $service->getSetup();

            expect($setup)->toBeInstanceOf(Setup::class);
            expect($setup->id)->toBeUuid();
            expect($setup->is_installed)->toBeFalse();
            expect($setup->completed_steps)->toBe([]);
        });

        it('returns existing setup record', function () {
            $existing = Setup::create([
                'is_installed' => false,
                'completed_steps' => ['welcome' => true],
            ]);

            $service = app(SetupService::class);
            $setup = $service->getSetup();

            expect($setup->id)->toBe($existing->id);
        });
    });

    describe('isInstalled', function () {
        it('returns false when not installed', function () {
            $service = app(SetupService::class);

            expect($service->isInstalled())->toBeFalse();
        });

        it('returns true when installed', function () {
            $setup = Setup::create(['is_installed' => true]);
            $setup->finalize('some-admin-id');

            $service = app(SetupService::class);

            expect($service->isInstalled())->toBeTrue();
        });
    });

    describe('generateToken', function () {
        it('generates and encrypts token', function () {
            $service = app(SetupService::class);

            $token = $service->generateToken();

            expect($token)->toBeString();
            expect(strlen($token))->toBe(64);

            $setup = $service->getSetup();
            expect($setup->setup_token_encrypted)->not->toBeNull();
            expect($setup->token_expires_at)->not->toBeNull();

            // Verify token can be decrypted
            $decrypted = decrypt($setup->setup_token_encrypted);
            expect($decrypted)->toBe($token);
        });
    });

    describe('validateToken', function () {
        it('returns true for valid token', function () {
            $service = app(SetupService::class);
            $token = $service->generateToken();

            expect($service->validateToken($token))->toBeTrue();
        });

        it('returns false for invalid token', function () {
            $service = app(SetupService::class);
            $service->generateToken();

            expect($service->validateToken('invalid-token'))->toBeFalse();
        });

        it('returns false for expired token', function () {
            $service = app(SetupService::class);
            $token = $service->generateToken();

            $setup = $service->getSetup();
            $setup->token_expires_at = now()->subHour();
            $setup->save();

            expect($service->validateToken($token))->toBeFalse();
        });
    });

    describe('completeStep', function () {
        it('marks step as completed', function () {
            $service = app(SetupService::class);

            $service->completeStep('welcome');

            $setup = $service->getSetup();
            expect($setup->isStepCompleted('welcome'))->toBeTrue();
        });

        it('stores related IDs', function () {
            $service = app(SetupService::class);

            $service->completeStep('school', ['school_id' => 'some-uuid']);

            $setup = $service->getSetup();
            expect($setup->school_id)->toBe('some-uuid');
        });
    });

    describe('finalize', function () {
        it('marks setup as installed and clears tokens', function () {
            $service = app(SetupService::class);
            $setup = $service->getSetup();
            $token = $service->generateToken();

            $service->finalize($setup, 'admin-uuid');

            $setup->refresh();
            expect($setup->is_installed)->toBeTrue();
            expect($setup->admin_id)->toBe('admin-uuid');
            expect($setup->setup_token_encrypted)->toBeNull();
            expect($setup->token_expires_at)->toBeNull();
        });
    });

    describe('getProgress', function () {
        it('calculates correct percentage', function () {
            $service = app(SetupService::class);
            $setup = $service->getSetup();

            expect($service->getProgress($setup))->toBe(0.0);

            $setup->completeStep('welcome');
            $setup->completeStep('school');

            expect($service->getProgress($setup))->toBe(40.0); // 2 of 5 steps
        });
    });
});
