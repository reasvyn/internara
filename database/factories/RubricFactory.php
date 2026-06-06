<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Assessment\Rubric\Models\Rubric;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RubricFactory extends Factory
{
    protected $model = Rubric::class;

    public function definition(): array
    {
        return [
            'internship_id' => Internship::factory(),
            'name' => fake()->unique()->words(3, true),
            'structure' => [
                'criteria' => [
                    [
                        'name' => 'Disiplin',
                        'weight' => 20,
                        'description' => 'Kehadiran dan kepatuhan aturan',
                    ],
                    [
                        'name' => 'Keterampilan',
                        'weight' => 50,
                        'description' => 'Kemampuan menyelesaikan tugas',
                    ],
                    [
                        'name' => 'Sikap',
                        'weight' => 30,
                        'description' => 'Kerjasama dan komunikasi',
                    ],
                ],
            ],
            'created_by' => User::factory(),
        ];
    }
}
