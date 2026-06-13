<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Evaluation\Models\EvaluationForm;
use App\Evaluation\Models\EvaluationSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationSectionFactory extends Factory
{
    protected $model = EvaluationSection::class;

    public function definition(): array
    {
        return [
            'form_id' => EvaluationForm::factory(),
            'title' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}
