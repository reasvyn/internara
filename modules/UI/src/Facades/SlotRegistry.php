<?php

declare(strict_types=1);

namespace Modules\UI\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\UI\Core\Contracts\SlotRegistry as SlotRegistryContract;

/**
 * @method static void configure(array $slots = [])
 * @method static void register(string $slot, string|\Closure|\Illuminate\Contracts\View\View $view, array $data = [])
 * @method static bool hasSlot(string $slot)
 *
 * @see \Modules\UI\Core\SlotRegistry
 */
class SlotRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SlotRegistryContract::class;
    }
}
