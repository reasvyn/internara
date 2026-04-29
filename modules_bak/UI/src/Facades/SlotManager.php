<?php

declare(strict_types=1);

namespace Modules\UI\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\UI\Core\Contracts\SlotManager as SlotManagerContract;

/**
 * @method static string render(string $slot)
 *
 * @see \Modules\UI\Core\SlotManager
 */
class SlotManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SlotManagerContract::class;
    }
}
