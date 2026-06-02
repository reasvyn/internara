<?php

declare(strict_types=1);

use App\Domain\Shared\Support\LangChecker;
use Illuminate\Translation\Translator;

describe('LangChecker', function () {
    it('extends the Translator class', function () {
        $reflection = new ReflectionClass(LangChecker::class);

        expect($reflection->isSubclassOf(Translator::class))->toBeTrue();
    });

    it('returns existing translation string', function () {
        $checker = app('translator');

        $result = $checker->get('common.language.switch');

        expect($result)->toBeString();
        expect($result)->not->toBe('common.language.switch');
    });

    it('returns missing key as-is', function () {
        $checker = app('translator');

        $result = $checker->get('this.key.does.not.exist.at.all');

        expect($result)->toBe('this.key.does.not.exist.at.all');
    });
});
