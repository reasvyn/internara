<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\Department;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Enums\BloodType;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Profile>
     */
    protected $model = Profile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
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
}
