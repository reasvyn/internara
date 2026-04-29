<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Modules\Setup\Models\Setup;
use Tests\TestCase;

/**
 * [S1 - Secure] Test encrypted token handling
 * [S2 - Sustain] Test clear model behavior
 * [S3 - Scalable] Test UUID primary key
 */
describe('Setup Model', function () {
    describe('UUID Generation', function () {
        it('generates UUID on creation', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);
            
            expect($setup->id)->toBeUuid();
        });
    });

    describe('Token Encryption', function () {
        it('encrypts token on setToken', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);
            
            $setup->setToken('plain-token-123');
            
            expect($setup->setup_token_encrypted)->not->toBe('plain-token-123');
            expect($setup->setup_token_encrypted)->toStartWith('eyJpdiI6'); // encrypted prefix
        });

        it('decrypts token on getToken', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);
            
            $setup->setToken('plain-token-123');
            $retrieved = $setup->getToken();
            
            expect($retrieved)->toBe('plain-token-123');
        });

        it('returns null for empty token', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);
            
            expect($setup->getToken())->toBeNull();
        });

        it('returns null on decryption failure', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);
            
            $setup->setup_token_encrypted = 'invalid-encrypted-data';
            $setup->save();
            
            Log::shouldReceive('warning');
            
            expect($setup->getToken())->toBeNull();
        });
    });

    describe('Token Validation', function () {
        it('validates correct token with timing-safe comparison', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);
            
            $setup->setToken('correct-token');
            
            expect($setup->tokenMatches('correct-token'))->toBeTrue();
            expect($setup->tokenMatches('wrong-token'))->toBeFalse();
        });
    });

    describe('Token Expiry', function () {
        it('detects expired token', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);
            
            $setup->setToken('test-token');
            $setup->token_expires_at = now()->subHour();
            $setup->save();
            
            expect($setup->isTokenExpired())->toBeTrue();
        });

        it('detects valid token', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);
            
            $setup->setToken('test-token');
            $setup->token_expires_at = now()->addHour();
            $setup->save();
            
            expect($setup->isTokenExpired())->toBeFalse();
        });

        it('returns true for null expiry', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);
            
            expect($setup->isTokenExpired())->toBeTrue();
        });
    });

    describe('Step Management', function () {
        it('completes steps', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);
            
            $setup->completeStep('welcome');
            
            expect($setup->isStepCompleted('welcome'))->toBeTrue();
            expect($setup->isStepCompleted('school'))->toBeFalse();
        });
    });

    describe('Finalization', function () {
        it('finalizes setup atomically', function () {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => ['welcome' => true],
                'setup_token_encrypted' => encrypt('test-token'),
                'token_expires_at' => now()->addDay(),
            ]);
            
            $setup->finalize('admin-uuid');
            
            $setup->refresh();
            expect($setup->is_installed)->toBeTrue();
            expect($setup->admin_id)->toBe('admin-uuid');
            expect($setup->setup_token_encrypted)->toBeNull();
            expect($setup->token_expires_at)->toBeNull();
        });
    });
});
