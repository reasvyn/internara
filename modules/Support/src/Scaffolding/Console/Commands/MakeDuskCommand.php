<?php

declare(strict_types=1);

namespace Modules\Support\Scaffolding\Console\Commands;

use Modules\Support\Scaffolding\Console\Concerns\HandlesModuleMakeGenerator;
use Nwidart\Modules\Commands\Make\GeneratorCommand;
use Nwidart\Modules\Support\Stub;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class MakeDuskCommand
 *
 * Generates a Laravel Dusk test within a module, respecting the project's
 * namespace and directory conventions.
 */
class MakeDuskCommand extends GeneratorCommand
{
    use HandlesModuleMakeGenerator;

    /**
     * The name and signature of the console command.
     */
    protected $name = 'module:make-dusk';

    /**
     * The console command description.
     */
    protected $description = 'Create a new Dusk test for the specified module.';

    /**
     * The argument name of the module.
     */
    protected $argumentName = 'name';

    /**
     * The configuration key for the command.
     */
    protected $configKey = 'test-browser';

    /**
     * Get the stub file path for the generator.
     */
    protected function getStub(): string
    {
        return '/tests/browser.stub';
    }

    /**
     * Get the template contents for the generator.
     */
    protected function getTemplateContents(): string
    {
        return (new Stub($this->getStub(), [
            'NAMESPACE' => $this->getTargetNamespace(),
        ]))->render();
    }

    /**
     * Get the destination file path for the generated class.
     */
    protected function getDestinationFilePath(): string
    {
        return $this->getTargetFilePath();
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the test.'],
            ['module', InputArgument::REQUIRED, 'The name of the module.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            [
                'force',
                null,
                InputOption::VALUE_NONE,
                'Create the test even if the test already exists.',
            ],
        ];
    }
}
