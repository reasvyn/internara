<?php

declare(strict_types=1);

namespace Modules\Support\Scaffolding\Console\Commands;

use Modules\Support\Scaffolding\Console\Concerns\HandlesModuleMakeGenerator;
use Nwidart\Modules\Commands\Make\GeneratorCommand;
use Nwidart\Modules\Support\Stub;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class MakeTraitCommand
 *
 * Generates a PHP trait within a module, respecting the project's
 * namespace and directory conventions.
 */
class MakeTraitCommand extends GeneratorCommand
{
    use HandlesModuleMakeGenerator;

    /**
     * The name and signature of the console command.
     */
    protected $name = 'module:make-trait';

    /**
     * The console command description.
     */
    protected $description = 'Create a new trait for the specified module, with a direct namespace.';

    /**
     * The argument name of the module.
     */
    protected $argumentName = 'name';

    /**
     * The configuration key for the command.
     */
    protected $configKey = 'traits';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return '/trait.stub';
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
     * Get the destination file path for the generated trait.
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
                'The name of the trait. Subdirectories are allowed (e.g., Concerns/MyTrait).',
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
                'Create the trait even if the trait already exists.',
            ],
        ];
    }
}
