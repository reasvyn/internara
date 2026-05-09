<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Mentor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MentorFactory extends Factory
{
    protected $model = Mentor::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement([Mentor::TYPE_SCHOOL_TEACHER, Mentor::TYPE_INDUSTRY_SUPERVISOR]),
            'is_active' => true,
        ];
    }

    public function schoolTeacher(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Mentor::TYPE_SCHOOL_TEACHER,
        ]);
    }

    public function industrySupervisor(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Mentor::TYPE_INDUSTRY_SUPERVISOR,
        ]);
    }
}
