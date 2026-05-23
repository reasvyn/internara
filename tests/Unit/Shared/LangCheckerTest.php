<?php

declare(strict_types=1);

use App\Domain\Shared\Support\LangChecker;
use Illuminate\Translation\Translator;

describe('LangChecker', function () {
    beforeEach(function () {
        config(['app.debug' => true]);
        (new \App\Providers\AppServiceProvider(app()))->register();
    });
    it('extends Laravel Translator', function () {
        $checker = app()->make('translator');

        expect($checker)->toBeInstanceOf(Translator::class);
    });

    it('returns translation for existing key', function () {
        $checker = app()->make('translator');
        $result = $checker->get('validation.accepted');

        expect($result)->toBeString()
            ->and($result)->not->toBe('validation.accepted');
    });

    it('returns the key itself for missing translation', function () {
        $checker = app()->make('translator');

        $result = $checker->get('nonexistent.key.xyz');

        expect($result)->toBe('nonexistent.key.xyz');
    });

    it('handles array translations', function () {
        $checker = app()->make('translator');

        $result = $checker->get('validation');

        expect($result)->toBeArray();
    });

    it('is instantiated as LangChecker', function () {
        $checker = app()->make('translator');

        expect($checker instanceof LangChecker)->toBeTrue();
    });
});
