<?php

declare(strict_types=1);

namespace Modules\Support\Scaffolding\Console\Commands;

use Modules\Support\Scaffolding\Console\Concerns\HandlesModuleMakeGenerator;
use Nwidart\Modules\Commands\Make\GeneratorCommand;
use Nwidart\Modules\Support\Stub;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class MakeInterfaceCommand
 *
 * Generates a PHP interface within a module, respecting the project's
 * namespace and directory conventions.
 */
class MakeInterfaceCommand extends GeneratorCommand
{
    use HandlesModuleMakeGenerator;

    /**
     * The name and signature of the console command.
     */
    protected $name = 'module:make-interface';

    /**
     * The console command description.
     */
    protected $description = 'Create a new interface for the specified module, with a direct namespace.';

    /**
     * The argument name of the module.
     */
    protected $argumentName = 'name';

    /**
     * The configuration key for the command.
     */
    protected $configKey = 'interfaces';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return '/interface.stub';
    }

    /**
     * Get the template contents for the generator.
     */
    protected function getTemplateContents(): string
    {
        return (new Stub($this->getStub(), [
            'NAMESPACE' => $this->getTargetNamespace(),
            'CLASS' => $this->getTargetName(),
        ]))->render();
    }

    /**
     * Get the destination file path for the generated interface.
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
            [
                'name',
                InputArgument::REQUIRED,
                'The name of the interface. Subdirectories are allowed (e.g., Services/SomeInterface).',
            ],
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
                'Create the interface even if the interface already exists.',
            ],
        ];
    }
}
