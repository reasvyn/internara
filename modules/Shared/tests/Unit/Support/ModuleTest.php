<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Module;
use Nwidart\Modules\Facades\Module as NwidartModule;

test('Module::isActive returns true for enabled module', function () {
    NwidartModule::shouldReceive('isEnabled')->with('Shared')->andReturn(true);

    expect(Module::isActive('Shared'))->toBeTrue()->and(is_active_module('Shared'))->toBeTrue();
});

test('Module::isActive returns false for disabled module', function () {
    NwidartModule::shouldReceive('isEnabled')->with('NonExistent')->andReturn(false);

    expect(Module::isActive('NonExistent'))
        ->toBeFalse()
        ->and(is_active_module('NonExistent'))
        ->toBeFalse();
});
