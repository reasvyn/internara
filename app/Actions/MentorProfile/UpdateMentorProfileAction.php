<?php

declare(strict_types=1);

namespace App\Actions\MentorProfile;

use App\Models\Mentor;

class UpdateMentorProfileAction
{
    public function execute(
        Mentor $mentor,
        string $type,
        ?string $employeeId = null,
        ?string $companyName = null,
        ?string $position = null,
        ?string $phone = null,
        ?string $bio = null,
        ?string $specialization = null,
    ): Mentor {
        $mentor->update([
            'type' => $type,
            'employee_id' => $employeeId,
            'company_name' => $companyName,
            'position' => $position,
            'phone' => $phone,
            'bio' => $bio,
            'specialization' => $specialization,
        ]);

        return $mentor->fresh();
    }
}
