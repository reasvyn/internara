<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Module;
use Nwidart\Modules\Facades\Module as NwidartModule;

test('Module::isActive returns true for enabled module', function () {
    $moduleMock = mock(\Nwidart\Modules\Module::class);
    $moduleMock->shouldReceive('isEnabled')->twice()->andReturn(true);

    NwidartModule::shouldReceive('find')->with('Shared')->twice()->andReturn($moduleMock);

    expect(Module::isActive('Shared'))->toBeTrue()->and(is_active_module('Shared'))->toBeTrue();
});

test('Module::isActive returns false for disabled module', function () {
    NwidartModule::shouldReceive('find')->with('NonExistent')->twice()->andReturn(null);

    expect(Module::isActive('NonExistent'))
        ->toBeFalse()
        ->and(is_active_module('NonExistent'))
        ->toBeFalse();
});
