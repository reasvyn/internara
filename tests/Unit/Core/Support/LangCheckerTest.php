<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Support;

use App\Core\Support\LangChecker;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Facades\Log;
use Mockery;

test('lang checker logs warning on missing translation key', function () {
    $loader = Mockery::mock(Loader::class);
    $loader->shouldReceive('load')
        ->andReturn([]);

    $log = Log::spy();

    $checker = new LangChecker($loader, 'en');
    $result = $checker->get('missing.key');

    expect($result)->toBe('missing.key');
    $log->shouldHaveReceived('warning')
        ->once()
        ->with('Missing translation key: missing.key', Mockery::type('array'));
});
