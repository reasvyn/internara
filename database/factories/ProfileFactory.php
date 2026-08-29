<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Enums\BloodType;
use App\Modules\User\Enums\Gender;
use App\Modules\User\Models\User;
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
