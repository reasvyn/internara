<?php

declare(strict_types=1);

use App\Domain\Core\Console\Commands\CacheWarmCommand;

describe('CacheWarmCommand', function () {
    it('has handle method', function () {
        expect(method_exists(CacheWarmCommand::class, 'handle'))->toBeTrue();
    });

    it('defines system:cache-warm signature', function () {
        $command = app(CacheWarmCommand::class);

        expect($command->getName())->toBe('system:cache-warm');
    });

});
