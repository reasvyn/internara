<?php

declare(strict_types=1);

namespace Modules\Support\Scaffolding\Console\Concerns;

use Illuminate\Support\Str;
use Modules\Shared\Support\Formatter;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Module as ModuleInstance;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Config\GeneratorPath;

/**
 * Trait HandlesModuleMakeGenerator
 *
 * Provides shared logic for module-specific Artisan generators, ensuring
 * consistent path resolution and namespace management.
 *
 * @mixin \Nwidart\Modules\Commands\Make\GeneratorCommand
 */
trait HandlesModuleMakeGenerator
{
    /**
     * Get the module instance being operated on.
     */
    protected function getModule(): ModuleInstance
    {
        return Module::findOrFail($this->argument('module'));
    }

    /**
     * Get the module name in StudlyCase.
     */
    protected function getModuleName(): string
    {
        return $this->getModule()->getStudlyName();
    }

    /**
     * Get the base namespace for the module.
     */
    protected function getModuleNamespace(): string
    {
        $moduleName = $this->getModuleName();
        $baseNamespace = (string) config('modules.namespace', 'Modules');

        return Formatter::namespace($baseNamespace, $moduleName);
    }

    /**
     * Get the target name for the generated file.
     */
    protected function getTargetName(): string
    {
        $name = (string) $this->argument('name');

        return Str::contains($name, '/') ? Str::afterLast($name, '/') : $name;
    }

    /**
     * Get the target namespace for the generated class.
     */
    protected function getTargetNamespace(): string
    {
        $appPath = Str::finish($this->getAppPath(), '/');
        $targetPath = str_replace($appPath, '', $this->getTargetPath());

        $moduleNamespace = $this->getModuleNamespace();
        $baseNamespace = str_replace('/', '\\', $targetPath);

        return Formatter::namespace($moduleNamespace, $baseNamespace);
    }

    /**
     * Get the full file path for the generated file.
     */
    protected function getTargetFilePath(): string
    {
        $filePath = Formatter::path($this->getTargetPath(), $this->getTargetName()).'.php';

        return module_path($this->getModule()->getName(), $filePath);
    }

    /**
     * Get the full target path for the generated file.
     */
    protected function getTargetPath(): string
    {
        $appPath = $this->getAppPath();
        $basePath = $this->getBasePath();
        $subPath = $this->getTargetSubPath();

        if (Str::startsWith($basePath, $appPath)) {
            return Formatter::path($basePath, $subPath);
        }

        return Formatter::path($appPath, $basePath, $subPath);
    }

    /**
     * Get the subdirectory path for the generated file.
     */
    protected function getTargetSubPath(): string
    {
        $name = (string) $this->argument('name');

        if (Str::contains($name, '/')) {
            return Formatter::path(Str::beforeLast($name, '/'));
        }

        return '';
    }

    /**
     * Get the base path for module files.
     */
    protected function getBasePath(): string
    {
        $appPath = Str::finish($this->getAppPath(), '/');
        $basePath = Str::after($this->getConfigReader($this->getConfigKey())->getPath(), $appPath);

        return Formatter::path($basePath);
    }

    /**
     * Get the application path for the module (e.g., src/).
     */
    protected function getAppPath(): string
    {
        $appPath = (string) config('modules.paths.app_folder', 'src/');

        return Formatter::path($appPath);
    }

    /**
     * Get the configuration key for the generator.
     */
    protected function getConfigKey(): string
    {
        return property_exists($this, 'configKey') ? $this->configKey : 'base';
    }

    /**
     * Get the configuration reader for a given key.
     */
    protected function getConfigReader(string $key): GeneratorPath
    {
        return GenerateConfigReader::read($key);
    }
}
