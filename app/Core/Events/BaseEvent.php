<?php

declare(strict_types=1);

namespace App\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    abstract public function eventName(): string;

    /**
     * Serialize constructor properties into a payload array.
     *
     * Key naming rules:
     * - Model properties → `{model}_id` (scalar primary key).
     *   Example: `public Assessment $assessment` becomes `['assessment_id' => 'uuid-...']`.
     *   Use `$event->assessment` (not payload) to access the full model object.
     * - Object with `toArray()` → recursively serialized under the original key.
     * - Scalars (string, int, bool, null) → kept as-is under the original key.
     * - Objects without `toArray()` → excluded from payload.
     */
    public function toPayload(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            if (str_starts_with($key, '__') || $key === 'socket') {
                continue;
            }

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

    public function broadcastOn(): array
    {
        return [];
    }

    public function broadcastAs(): string
    {
        return $this->eventName();
    }

    public function shouldBroadcast(): bool
    {
        return false;
    }

    public function shouldQueue(): bool
    {
        return false;
    }

    public function queue(): string
    {
        return 'default';
    }
}
