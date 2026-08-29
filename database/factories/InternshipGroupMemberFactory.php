<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternshipGroupMemberFactory extends Factory
{
    protected $model = InternshipGroupMember::class;

    public function definition(): array
    {
        return [
            'internship_group_id' => InternshipGroup::factory(),
            'registration_id' => Registration::factory(),
            'user_id' => null,
            'joined_at' => now(),
        ];
    }
}
