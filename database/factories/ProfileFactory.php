<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Academics\Department\Models\Department;
use App\User\Enums\BloodType;
use App\User\Enums\Gender;
use App\User\Models\User;
use App\User\Profile\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'gender' => $this->faker->randomElement([Gender::MALE, Gender::FEMALE]),
            'blood_type' => $this->faker->randomElement(BloodType::cases()),
            'pob' => $this->faker->city(),
            'dob' => $this->faker->date(),
            'emergency_contact' => [
                'name' => $this->faker->name(),
                'phone' => $this->faker->phoneNumber(),
                'address' => $this->faker->address(),
            ],
            'id_number' => null,
            'internal_notes' => $this->faker->sentence(),
            'department_id' => Department::factory(),
            'company_id' => null,
        ];
    }

    public function forStudent(Department|int|null $department = null): static
    {
        return $this->state(
            fn () => [
                'id_number' => $this->faker->unique()->numerify('STD-#####'),
                'department_id' => $department instanceof Department
                        ? $department->id
                        : $department ?? Department::factory(),
            ],
        );
    }

    public function forTeacher(): static
    {
        return $this->state(
            fn () => [
                'id_number' => $this->faker->unique()->numerify('NIP-##########'),
            ],
        );
    }

    public function forSupervisor(): static
    {
        return $this->state(
            fn () => [
                'id_number' => $this->faker->unique()->numerify('SUP-##########'),
                'department_id' => null,
            ],
        );
    }

    public function male(): static
    {
        return $this->state(fn () => ['gender' => Gender::MALE]);
    }

    public function female(): static
    {
        return $this->state(fn () => ['gender' => Gender::FEMALE]);
    }
}
