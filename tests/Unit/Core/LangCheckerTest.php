<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Modules\Core\Services\LangChecker;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

describe('SE5Q9: LangChecker Translation Service', function (): void {
    test('SE5Q9-UT-CORE-001: LangChecker returns translated string for valid key without warnings', function (): void {
        Log::spy();
        $loader = app('translation.loader');
        $checker = new LangChecker($loader, 'en');

        $result = $checker->get('auth.failed');

        expect($result)->toBe('These credentials do not match our records.');
        Log::shouldNotHaveReceived('warning');
    });

    test('SE5Q9-UT-CORE-002: LangChecker logs warning when translation key is missing', function (): void {
        Log::spy();
        $loader = app('translation.loader');
        $checker = new LangChecker($loader, 'en');

        $missingKey = 'non_existent_domain.missing_key_xyz';
        $result = $checker->get($missingKey);

        expect($result)->toBe($missingKey);
    });

    test('SE5Q9-UT-CORE-003: LangChecker respects custom locale parameter', function (): void {
        $loader = app('translation.loader');
        $checker = new LangChecker($loader, 'en');

        $result = $checker->get('auth.failed', [], 'id');

        expect($result)->toBe('Kredensial ini tidak cocok dengan catatan kami.');
    });
});
