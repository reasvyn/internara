<?php

declare(strict_types=1);

namespace Modules\Support\Scaffolding\Console\Commands;

use Modules\Support\Scaffolding\Console\Concerns\HandlesModuleMakeGenerator;
use Nwidart\Modules\Commands\Make\GeneratorCommand;
use Nwidart\Modules\Support\Stub;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class MakeClassCommand
 *
 * Generates a plain PHP class within a module, respecting the project's
 * namespace and directory conventions.
 */
class MakeClassCommand extends GeneratorCommand
{
    use HandlesModuleMakeGenerator;

    /**
     * The name and signature of the console command.
     */
    protected $name = 'module:make-class';

    /**
     * The console command description.
     */
    protected $description = 'Create a new plain class for the specified module, with a direct namespace.';

    /**
     * The argument name of the module.
     */
    protected $argumentName = 'name';

    /**
     * The configuration key for the command.
     */
    protected $configKey = 'class';

    /**
     * Get the stub file path for the generator.
     */
    protected function getStub(): string
    {
        return '/class.stub';
    }

    /**
     * Get the template contents for the generator.
     */
    protected function getTemplateContents(): string
    {
        $interface = $this->option('interface');
        $implements = '';
        $imports = '';

        if ($interface) {
            $interfaceName = class_basename($interface);
            $implements = " implements $interfaceName";
            $imports = "use $interface;\n";
        }

        return (new Stub($this->getStub(), [
            'NAMESPACE' => $this->getTargetNamespace(),
            'IMPORTS' => $imports,
            'CLASS' => $this->getTargetName(),
            'IMPLEMENTS' => $implements,
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
            ['name', InputArgument::REQUIRED, 'The name of the class.'],
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
                'Create the class even if the class already exists.',
            ],
            [
                'interface',
                'i',
                InputOption::VALUE_OPTIONAL,
                'The interface that the class should implement.',
            ],
        ];
    }
}
