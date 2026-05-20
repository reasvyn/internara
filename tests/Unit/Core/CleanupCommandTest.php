<?php

declare(strict_types=1);

use App\Domain\Core\Console\Commands\CleanupCommand;

describe('CleanupCommand', function () {
    it('has handle method', function () {
        expect(method_exists(CleanupCommand::class, 'handle'))->toBeTrue();
    });

    it('defines system:cleanup signature', function () {
        $command = app(CleanupCommand::class);

        expect($command->getName())->toBe('system:cleanup');
    });

    it('has --force option', function () {
        $command = app(CleanupCommand::class);
        $definition = $command->getDefinition();

        expect($definition->hasOption('force'))->toBeTrue();
    });

    it('has --log-retention option with default 30', function () {
        $command = app(CleanupCommand::class);
        $option = $command->getDefinition()->getOption('log-retention');

        expect($option->getDefault())->toBe('30');
    });
});
