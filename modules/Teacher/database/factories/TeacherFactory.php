<?php

declare(strict_types=1);

namespace Modules\Teacher\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Teacher\Models\Teacher;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Teacher\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Modules\Teacher\Models\Teacher>
     */
    protected $model = Teacher::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nip' => (string) fake()
                ->unique()
                ->numberBetween(100000000000000000, 999999999999999999),
        ];
    }
}
