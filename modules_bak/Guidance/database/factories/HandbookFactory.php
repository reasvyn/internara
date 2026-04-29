<?php

declare(strict_types=1);

namespace Modules\Guidance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Guidance\Models\Handbook;

class HandbookFactory extends Factory
{
    protected $model = Handbook::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'version' => '1.0',
            'is_active' => true,
            'is_mandatory' => false,
        ];
    }
}
