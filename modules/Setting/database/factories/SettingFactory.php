<?php

declare(strict_types=1);

namespace Modules\Setting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Setting\Models\Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Defaults to a 'string' type setting.
        return $this->buildState('string');
    }

    /**
     * Indicate that the setting should be of type 'string'.
     *
     * @param array<string, mixed> $attributes
     */
    public function string(array $attributes = []): static
    {
        return $this->state(fn(array $_) => $this->buildState('string', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'integer'.
     *
     * @param array<string, mixed> $attributes
     */
    public function integer(array $attributes = []): static
    {
        return $this->state(fn(array $_) => $this->buildState('integer', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'float'.
     *
     * @param array<string, mixed> $attributes
     */
    public function float(array $attributes = []): static
    {
        return $this->state(fn(array $_) => $this->buildState('float', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'boolean'.
     *
     * @param array<string, mixed> $attributes
     */
    public function boolean(array $attributes = []): static
    {
        return $this->state(fn(array $_) => $this->buildState('boolean', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'array'.
     *
     * @param array<string, mixed> $attributes
     */
    public function array(array $attributes = []): static
    {
        return $this->state(fn(array $_) => $this->buildState('array', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'json'.
     *
     * @param array<string, mixed> $attributes
     */
    public function json(array $attributes = []): static
    {
        return $this->state(fn(array $_) => $this->buildState('json', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'null'.
     *
     * @param array<string, mixed> $attributes
     */
    public function nullType(array $attributes = []): static
    {
        return $this->state(fn(array $_) => $this->buildState('null', $attributes));
    }

    /**
     * Build a state array with default values for a given setting type,
     * merged with any provided attribute overrides.
     *
     * @param string $type The type of the setting ('string', 'integer', etc.).
     * @param array<string, mixed> $attributes Attributes to override the defaults.
     *
     * @return array<string, mixed>
     */
    protected function buildState(string $type, array $attributes = []): array
    {
        $defaultValue = match ($type) {
            'integer' => $this->faker->numberBetween(1, 100),
            'float' => $this->faker->randomFloat(2, 0, 1000),
            'boolean' => $this->faker->boolean(),
            'array' => ['item1' => $this->faker->word(), 'item2' => $this->faker->word()],
            'json' => [
                'data_key' => $this->faker->word(),
                'data_value' => $this->faker->sentence(),
            ],
            'null' => null,
            default => $this->faker->sentence(), // 'string'
        };

        // Harmonize the 'type' attribute with what the SettingValueCast expects for storage
        $dbType = match ($type) {
            'array', 'object', 'json' => 'json', // Add 'json' here
            'boolean' => 'boolean',
            'integer' => 'integer',
            'float' => 'float',
            'null' => 'null', // Null is stored as 'null' type
            default => 'string', // All other types (including 'string' itself)
        };

        $defaults = [
            'key' => $this->faker->unique()->slug(2),
            'value' => $defaultValue,
            'type' => $dbType, // Use the harmonized dbType
            'description' => $this->faker->paragraph(),
            'group' => $this->faker->word(),
        ];

        return array_merge($defaults, $attributes);
    }
}
