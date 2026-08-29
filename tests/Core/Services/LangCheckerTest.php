<?php

declare(strict_types=1);

use App\Modules\Core\Services\LangChecker;

function makeLangChecker(string $locale): LangChecker
{
    return new LangChecker(app('translator')->getLoader(), $locale);
}

test('C8F0D-FR-SUP10: get() returns the translation for an existing key', function () {
    expect(makeLangChecker('en')->get('core.csv.created'))->toBe('Created');
});

test('C8F0D-FR-SUP10: get() returns the key itself and warns for a missing key', function () {
    $logs = captureLogs();

    $result = makeLangChecker('en')->get('core.nonexistent_key');

    expect($result)->toBe('core.nonexistent_key');

    $warning = $logs->firstWhere('level', 'warning');
    expect($warning)->not->toBeNull();
    expect($warning->message)->toContain('Missing translation key: core.nonexistent_key');
    expect($warning->context['payload']['locale'])->toBe('en');
});
