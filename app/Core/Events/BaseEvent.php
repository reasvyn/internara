<?php

declare(strict_types=1);

namespace App\Core\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

abstract class BaseEvent
{
    use Dispatchable;

    abstract public function eventName(): string;

    public function toPayload(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($value instanceof Model) {
                $result[$key.'_id'] = $value->getKey();
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $result[$key] = $value->toArray();
            } elseif (! is_object($value)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
