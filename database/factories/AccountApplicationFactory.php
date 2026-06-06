<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Academics\Department\Models\Department;
use App\Enrollment\Models\AccountApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountApplicationFactory extends Factory
{
    protected $model = AccountApplication::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'student_id_number' => 'NISN-'.$this->faker->unique()->numberBetween(10000000, 99999999),
            'department_id' => Department::factory(),
            'form_data' => [
                'phone' => $this->faker->phoneNumber(),
                'address' => $this->faker->address(),
                'entry_year' => $this->faker->year(),
                'class_name' => 'XII-RPL-1',
            ],
            'status' => 'pending',
            'rejection_reason' => null,
            'processed_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'approved']);
    }
}
