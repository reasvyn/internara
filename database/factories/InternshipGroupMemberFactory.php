<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Internship\Models\InternshipGroup;
use App\Domain\Internship\Models\InternshipGroupMember;
use App\Domain\Registration\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternshipGroupMemberFactory extends Factory
{
    protected $model = InternshipGroupMember::class;

    public function definition(): array
    {
        return [
            'internship_group_id' => InternshipGroup::factory(),
            'registration_id' => Registration::factory(),
            'role' => 'student',
        ];
    }
}
