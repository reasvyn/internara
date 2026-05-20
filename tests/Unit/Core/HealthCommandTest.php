<?php

declare(strict_types=1);

use App\Domain\Core\Console\Commands\CacheWarmCommand;
use App\Domain\Core\Console\Commands\HealthCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

describe('HealthCommand', function () {
    it('has handle method', function () {
        expect(method_exists(HealthCommand::class, 'handle'))->toBeTrue();
    });

    it('defines system:health signature', function () {
        $command = app(HealthCommand::class);

        expect($command->getName())->toBe('system:health');
    });

    it('runs with JSON output', function () {
        $command = app(HealthCommand::class);
        $command->setLaravel(app());

        $exitCode = $command->run(
            new ArrayInput(['--json' => true]),
            new NullOutput,
        );

        expect($exitCode)->toBe(0);
    });
});

describe('CacheWarmCommand', function () {
    it('has handle method', function () {
        expect(method_exists(CacheWarmCommand::class, 'handle'))->toBeTrue();
    });

    it('defines system:cache-warm signature', function () {
        $command = app(CacheWarmCommand::class);

        expect($command->getName())->toBe('system:cache-warm');
    });
});
