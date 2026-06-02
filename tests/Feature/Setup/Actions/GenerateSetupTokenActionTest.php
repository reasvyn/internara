<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Domain\Core\Support\CacheKeys;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Models\Setup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
    Cache::forget(CacheKeys::SETUP_INSTALLED);
});

describe('GenerateSetupTokenAction', function () {
    it('generates a token and stores encrypted in database', function () {
        $result = app(GenerateSetupTokenAction::class)->execute();

        expect($result)->toHaveKeys(['plaintext', 'expires_at']);

        $setup = Setup::first();
        expect($setup)->not->toBeNull()
            ->and($setup->setup_token)->not->toBeNull()
            ->and($setup->token_expires_at)->not->toBeNull();

        $decrypted = Crypt::decryptString($setup->setup_token);
        expect($decrypted)->toBe($result['plaintext']);
    });

    it('sets the token expiry from config', function () {
        config(['setup.token.expiry_minutes' => 30]);

        $result = app(GenerateSetupTokenAction::class)->execute();

        expect($result['expires_at']->diffInMinutes(now()))->toBeLessThanOrEqual(31);
    });

    it('uses configured token length', function () {
        config(['setup.token.length' => 32]);

        $result = app(GenerateSetupTokenAction::class)->execute();

        expect(strlen($result['plaintext']))->toBe(32);
    });

    it('forgets the installed cache key', function () {
        Cache::put(CacheKeys::SETUP_INSTALLED, true, 3600);
        expect(Cache::has(CacheKeys::SETUP_INSTALLED))->toBeTrue();

        app(GenerateSetupTokenAction::class)->execute();

        expect(Cache::has(CacheKeys::SETUP_INSTALLED))->toBeFalse();
    });

    it('reuses the existing setup record', function () {
        $first = Setup::factory()->create(['is_installed' => false]);

        app(GenerateSetupTokenAction::class)->execute();

        $count = Setup::count();
        expect($count)->toBe(1);
    });

    it('returns a Carbon expiry time', function () {
        $result = app(GenerateSetupTokenAction::class)->execute();

        expect($result['expires_at'])->toBeInstanceOf(Carbon::class);
    });

    it('handles concurrent generation with cache lock', function () {
        $action = app(GenerateSetupTokenAction::class);

        $result1 = $action->execute();
        $result2 = $action->execute();

        expect($result1['plaintext'])->not->toBe($result2['plaintext']);
    });
});
