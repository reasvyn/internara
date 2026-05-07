<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Mentor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Mentor model.
 */
class MentorFactory extends Factory
{
    protected $model = Mentor::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([Mentor::TYPE_SCHOOL_TEACHER, Mentor::TYPE_INDUSTRY_SUPERVISOR]);

        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'type' => $type,
            'employee_id' => $type === Mentor::TYPE_SCHOOL_TEACHER ? strtoupper($this->faker->lexify('EMP-???')) : null,
            'company_name' => $type === Mentor::TYPE_INDUSTRY_SUPERVISOR ? $this->faker->company : null,
            'position' => $this->faker->jobTitle,
            'phone' => $this->faker->phoneNumber,
            'bio' => $this->faker->optional()->paragraph(),
            'specialization' => implode(', ', $this->faker->randomElements(['Web Development', 'Data Science', 'Networking', 'Cybersecurity', 'Cloud Computing'], rand(1, 3))),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the mentor is a school teacher.
     */
    public function schoolTeacher(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Mentor::TYPE_SCHOOL_TEACHER,
        ]);
    }

    /**
     * Indicate that the mentor is an industry supervisor.
     */
    public function industrySupervisor(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Mentor::TYPE_INDUSTRY_SUPERVISOR,
        ]);
    }
}
