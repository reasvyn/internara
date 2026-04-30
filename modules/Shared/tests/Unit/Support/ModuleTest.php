<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Module;
use Nwidart\Modules\Facades\Module as NwidartModule;

describe('Module Support Utility', function () {
    test('it returns true for active modules', function () {
        $moduleMock = \Mockery::mock(\Nwidart\Modules\Module::class);
        $moduleMock->shouldReceive('isEnabled')->twice()->andReturn(true);

        NwidartModule::shouldReceive('find')->with('Shared')->twice()->andReturn($moduleMock);

        expect(Module::isActive('Shared'))->toBeTrue()->and(is_active_module('Shared'))->toBeTrue();
    });

    test('it returns false for inactive or missing modules', function () {
        NwidartModule::shouldReceive('find')->with('NonExistent')->twice()->andReturn(null);

        expect(Module::isActive('NonExistent'))
            ->toBeFalse()
            ->and(is_active_module('NonExistent'))
            ->toBeFalse();
    });
});
