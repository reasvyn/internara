<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Core\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Setting>
     */
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return $this->buildState('string');
    }

    /**
     * Indicate that the setting should be of type 'string'.
     *
     * @param array<string, mixed> $attributes
     */
    public function string(array $attributes = []): static
    {
        return $this->state(fn () => $this->buildState('string', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'integer'.
     *
     * @param array<string, mixed> $attributes
     */
    public function integer(array $attributes = []): static
    {
        return $this->state(fn () => $this->buildState('integer', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'float'.
     *
     * @param array<string, mixed> $attributes
     */
    public function float(array $attributes = []): static
    {
        return $this->state(fn () => $this->buildState('float', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'boolean'.
     *
     * @param array<string, mixed> $attributes
     */
    public function boolean(array $attributes = []): static
    {
        return $this->state(fn () => $this->buildState('boolean', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'json'.
     *
     * @param array<string, mixed> $attributes
     */
    public function json(array $attributes = []): static
    {
        return $this->state(fn () => $this->buildState('json', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'null'.
     *
     * @param array<string, mixed> $attributes
     */
    public function nullType(array $attributes = []): static
    {
        return $this->state(fn () => $this->buildState('null', $attributes));
    }

    /**
     * Indicate that the setting should be of type 'encrypted'.
     *
     * @param array<string, mixed> $attributes
     */
    public function encrypted(array $attributes = []): static
    {
        return $this->state(fn () => $this->buildState('encrypted', $attributes));
    }

    /**
     * Build a state array with default values for a given setting type.
     *
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    protected function buildState(string $type, array $attributes = []): array
    {
        $defaultValue = match ($type) {
            'integer' => $this->faker->numberBetween(1, 100),
            'float' => $this->faker->randomFloat(2, 0, 1000),
            'boolean' => $this->faker->boolean(),
            'json' => [
                'data_key' => $this->faker->word(),
                'data_value' => $this->faker->sentence(),
            ],
            'null' => null,
            'encrypted' => Crypt::encryptString($this->faker->password()),
            default => $this->faker->sentence(),
        };

        $dbType = match ($type) {
            'json' => 'json',
            'boolean' => 'boolean',
            'integer' => 'integer',
            'float' => 'float',
            'null' => 'null',
            'encrypted' => 'encrypted',
            default => 'string',
        };

        $defaults = [
            'key' => $this->faker->unique()->slug(2),
            'value' => $defaultValue,
            'type' => $dbType,
            'description' => $this->faker->paragraph(),
            'group' => $this->faker->word(),
        ];

        return array_merge($defaults, $attributes);
    }
}
