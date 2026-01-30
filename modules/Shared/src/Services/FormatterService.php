<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use Illuminate\Support\Str;
use Modules\Shared\Services\Contracts\FormatterService as Contract;

class FormatterService implements Contract
{
    /**
     * {@inheritDoc}
     */
    public function path(?string ...$paths): string
    {
        $path = implode('/', array_filter($paths, fn ($path) => ! empty($path)));

        return $this->normalizePath($path);
    }

    /**
     * Normalize path string.
     */
    protected function normalizePath(string $path = ''): string
    {
        $path = Str::replace('//', '/', $path);
        $path = Str::startsWith($path, '/') ? Str::replaceFirst('/', '', $path) : $path;
        $path = Str::endsWith($path, '/') ? Str::replaceLast('/', '', $path) : $path;
        $path = Str::replace('//', '/', $path);

        return $path;
    }

    /**
     * {@inheritDoc}
     */
    public function namespace(?string ...$parts): string
    {
        $namespace = implode('\\', array_filter($parts, fn ($namespace) => ! empty($namespace)));

        return $this->normalizeNamespace($namespace);
    }

    /**
     * Normalize namespace string.
     */
    protected function normalizeNamespace(string $namespace): string
    {
        $namespace = Str::replace(['/', '//', '\\'], '\\', $namespace);
        $namespace = Str::startsWith($namespace, '\\')
            ? Str::replaceFirst('\\', '', $namespace)
            : $namespace;
        $namespace = Str::endsWith($namespace, '\\')
            ? Str::replaceLast('\\', '', $namespace)
            : $namespace;
        $namespace = Str::replace('\\\\', '\\', $namespace);

        return $namespace;
    }
}
