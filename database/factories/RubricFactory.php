<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
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
                        'name' => 'Internalisasi Soft Skills',
                        'weight' => 20,
                        'description' => 'Internalisasi karakter kerja, etika profesi, kedisiplinan kehadiran, kepatuhan K3, dan komunikasi tim',
                    ],
                    [
                        'name' => 'Penerapan Hard Skills',
                        'weight' => 60,
                        'description' => 'Penerapan kompetensi teknis kejuruan, ketepatan penyelesaian tugas spesifik, dan pemecahan masalah praktis',
                    ],
                    [
                        'name' => 'Penyiapan Kewirausahaan',
                        'weight' => 20,
                        'description' => 'Pemahaman alur proses bisnis industri, inisiatif mandiri, kreativitas, dan efisiensi kerja berorientasi wirausaha',
                    ],
                ],
            ],
            'created_by' => User::factory(),
        ];
    }
}
