<?php

declare(strict_types=1);

namespace Modules\Exception;

use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFound;
use Throwable;

/**
 * Class Handler
 *
 * Provides standardized exception transformation logic for the modular monolith.
 * This class centralizes the mapping of infrastructure-level exceptions to
 * localized domain-specific exceptions.
 */
final class Handler
{
    /**
     * Map infrastructure exceptions to localized domain exceptions.
     */
    public static function map(Throwable $e): Throwable
    {
        if ($e instanceof EloquentModelNotFound) {
            $ids = $e->getIds();
            $uuid = ! empty($ids) ? (string) $ids[0] : 'multiple';

            // Extract module name from model namespace (Modules\{Module}\Models\...)
            $modelClass = $e->getModel();
            $module = 'shared';

            if (preg_match('/Modules\\\\([^\\\\]+)/', $modelClass, $matches)) {
                $module = strtolower($matches[1]);
            }

            return RecordNotFoundException::for($uuid, $module);
        }

        return $e;
    }
}
