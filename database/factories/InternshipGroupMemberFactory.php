<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enrollment\Models\Registration;
use App\Program\Internship\Models\InternshipGroup;
use App\Program\Internship\Models\InternshipGroupMember;
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
