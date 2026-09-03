<?php

declare(strict_types=1);

use App\Modules\Core\Services\AppInfo;
use Illuminate\Support\Collection;

if (! function_exists('app_info')) {
    /**
     * Get application metadata from Composer (SSoT) and config.
     *
     * S2 - Sustain: Centralized access to Composer metadata.
     *
     * @param string|null $key Metadata key (name, version, author, etc.)
     * @param mixed $default Default value when key is not found
     */
    function app_info(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return AppInfo::all();
        }

        return AppInfo::get($key, $default);
    }
}

if (! function_exists('ts_options')) {
    /**
     * Normalize any option source into the shape TallStackUI selects expect.
     *
     * TallStackUI discards array keys (SelectSetup::setup() calls array_values())
     * and then requires every option to expose a `label` and a `value` key, so
     * passing an Eloquent collection, a `value => label` map, or a `[null => ...]`
     * placeholder union all silently produce empty or mislabeled dropdowns.
     *
     * @param iterable<mixed>|null $items Models, arrays, or a `value => label` map
     * @param string|null $placeholder Prepended entry rendered with an empty value
     * @param string $label Key/attribute holding the visible text (dot notation allowed)
     * @param string $value Key/attribute holding the submitted value (dot notation allowed)
     *
     * @return array<int, array{label: string, value: string}>
     */
    function ts_options(
        ?iterable $items = [],
        ?string $placeholder = null,
        string $label = 'name',
        string $value = 'id',
    ): array {
        $options = [];

        if ($placeholder !== null) {
            $options[] = ['label' => $placeholder, 'value' => ''];
        }

        $items = $items instanceof Collection
            ? $items->all()
            : (is_array($items) ? $items : iterator_to_array($items));

        foreach ($items as $key => $item) {
            if ($item instanceof BackedEnum) {
                $options[] = [
                    'label' => method_exists($item, 'label')
                        ? $item->label()
                        : (string) $item->value,
                    'value' => (string) $item->value,
                ];

                continue;
            }

            // A `value => label` map: the key carries the value, the item the text.
            if (is_scalar($item) || $item === null) {
                $options[] = [
                    'label' => (string) $item,
                    'value' => is_int($key) ? (string) $item : (string) $key,
                ];

                continue;
            }

            $options[] = [
                'label' => (string) (data_get($item, $label) ?? ''),
                'value' => (string) (data_get($item, $value) ?? ''),
            ];
        }

        return $options;
    }
}
