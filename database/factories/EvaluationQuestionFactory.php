<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Evaluation\Models\EvaluationForm;
use App\Evaluation\Models\EvaluationQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationQuestionFactory extends Factory
{
    protected $model = EvaluationQuestion::class;

    public function definition(): array
    {
        return [
            'form_id' => EvaluationForm::factory(),
            'section_id' => null,
            'question_text' => fake()->sentence(),
            'question_type' => 'rating_1_5',
            'options' => null,
            'weight' => 1,
            'order' => fake()->numberBetween(0, 10),
            'is_required' => true,
        ];
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attrs) => [
            'question_type' => 'multiple_choice',
            'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang'],
        ]);
    }

    public function text(): static
    {
        return $this->state(fn (array $attrs) => [
            'question_type' => 'text',
            'is_required' => false,
        ]);
    }
}
