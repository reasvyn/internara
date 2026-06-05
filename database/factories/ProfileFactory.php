<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Academics\Department\Models\Department;
use App\Academics\School\Models\School;
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
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_contact_address' => $this->faker->address(),
            'bio' => $this->faker->sentence(),
            'national_id_number' => $this->faker->numerify('##############'),
            'student_id_number' => $this->faker->unique()->numerify('STU-#####'),
            'school_id' => School::factory(),
            'department_id' => Department::factory(),
        ];
    }

    public function forStudent(Department|int|null $department = null): static
    {
        return $this->state(fn () => [
            'national_id_number' => $this->faker->numerify('##############'),
            'student_id_number' => $this->faker->unique()->numerify('STD-#####'),
            'department_id' => $department instanceof Department ? $department->id : $department ?? Department::factory(),
        ]);
    }

    public function forTeacher(): static
    {
        return $this->state(fn () => [
            'national_id_number' => null,
            'student_id_number' => $this->faker->unique()->numerify('NIP-##########'),
        ]);
    }

    public function forSupervisor(): static
    {
        return $this->state(fn () => [
            'national_id_number' => null,
            'student_id_number' => null,
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
