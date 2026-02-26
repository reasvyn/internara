<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Base abstract class for all domain services.
 *
 * This class provides a common foundation for services that focus on business logic
 * orchestration rather than direct Eloquent persistence.
 */
abstract class BaseService
{
    /**
     * Executes a callback within a database transaction.
     *
     * This provides a reliable, standardized way to ensure atomicity for complex
     * business operations involving multiple database interactions.
     *
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @return TReturn
     * @throws Throwable
     */
    protected function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
