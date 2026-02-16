<?php

declare(strict_types=1);

namespace Modules\Student\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Student\Models\Student;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Student\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Modules\Student\Models\Student>
     */
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_number' => (string) fake()->unique()->numberBetween(10000000, 99999999),
            'national_identifier' => (string) fake()
                ->unique()
                ->numberBetween(1000000000, 9999999999),
            'class_name' => fake()->randomElement(['X', 'XI', 'XII']).' '.fake()->word(),
        ];
    }
}
