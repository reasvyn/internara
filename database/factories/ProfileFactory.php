<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\Department;
use App\Domain\User\Enums\BloodType;
use App\Domain\User\Enums\Gender;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
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
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_contact_address' => $this->faker->address(),
            'bio' => $this->faker->sentence(),
            'national_identifier' => $this->faker->numerify('##############'),
            'registration_number' => $this->faker->unique()->numerify('REG-#####'),
            'department_id' => Department::factory(),
        ];
    }

    public function forStudent(Department|int|null $department = null): static
    {
        return $this->state(fn () => [
            'national_identifier' => $this->faker->numerify('##############'),
            'registration_number' => $this->faker->unique()->numerify('STD-#####'),
            'department_id' => $department instanceof Department ? $department->id : $department ?? Department::factory(),
        ]);
    }

    public function forTeacher(): static
    {
        return $this->state(fn () => [
            'national_identifier' => null,
            'registration_number' => $this->faker->unique()->numerify('NIP-##########'),
        ]);
    }

    public function forSupervisor(): static
    {
        return $this->state(fn () => [
            'national_identifier' => null,
            'registration_number' => null,
            'department_id' => null,
        ]);
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
