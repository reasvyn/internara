<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Support;

use App\Core\Support\LangChecker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Mockery;

describe('LangChecker with real translations', function () {
    it('returns real translation from lang files', function () {
        $result = App::make('translator')->get('log.login_success', [], 'en');

        expect($result)->toBe('User has successfully authenticated into the system.');
    });

    it('returns translated value in Indonesian', function () {
        $result = App::make('translator')->get('log.login_success', [], 'id');

        expect($result)->toBeString();
        expect($result)->not->toBe('log.login_success');
    });

    it('returns key when translation missing', function () {
        $result = App::make('translator')->get('log.nonexistent_key', [], 'en');

        expect($result)->toBe('log.nonexistent_key');
    });

    it('detects missing keys', function () {
        $log = Log::spy();

        $checker = new LangChecker(App::make('translation.loader'), 'en');
        $result = $checker->get('log.nonexistent_key', [], 'en');

        expect($result)->toBe('log.nonexistent_key');
        $log->shouldHaveReceived('warning')
            ->once()
            ->with('Missing translation key: log.nonexistent_key', Mockery::type('array'));
    });

    it('does not warn for existing keys', function () {
        $log = Log::spy();

        $checker = new LangChecker(App::make('translation.loader'), 'en');
        $result = $checker->get('log.login_success', [], 'en');

        expect($result)->toBe('User has successfully authenticated into the system.');
        $log->shouldNotHaveReceived('warning');
    });

    it('handles missing key with empty string', function () {
        $log = Log::spy();

        $checker = new LangChecker(App::make('translation.loader'), 'en');
        $result = $checker->get('', [], 'en');

        expect($result)->toBe('');
        $log->shouldHaveReceived('warning')
            ->once()
            ->with('Missing translation key: ', Mockery::type('array'));
    });

    it('uses specified locale', function () {
        $checker = new LangChecker(App::make('translation.loader'), 'id');
        $result = $checker->get('log.login_success', [], 'id');

        expect($result)->toBeString();
        expect($result)->not->toBe('log.login_success');
    });

    it('falls back to default locale', function () {
        $checker = new LangChecker(App::make('translation.loader'), 'en');
        $result = $checker->get('log.login_success', [], 'es');

        expect($result)->toBeString();
        expect($result)->toBe('log.login_success');
    });

    it('replaces placeholders', function () {
        $result = App::make('translator')->get('setup.wizard.page_title', ['app_name' => 'Internara'], 'en');

        expect($result)->toBeString();
        expect($result)->toContain('Internara');
    });

    it('caller detection works', function () {
        $log = Log::spy();

        $checker = new LangChecker(App::make('translation.loader'), 'en');
        $checker->get('log.another_missing_key', [], 'en');

        $log->shouldHaveReceived('warning')
            ->once()
            ->with('Missing translation key: log.another_missing_key', Mockery::type('array'));
    });
});
