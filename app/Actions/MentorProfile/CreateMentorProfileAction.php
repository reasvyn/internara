<?php

declare(strict_types=1);

namespace App\Actions\MentorProfile;

use App\Models\Mentor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateMentorProfileAction
{
    public function execute(
        string $userId,
        string $type,
        ?string $employeeId = null,
        ?string $companyName = null,
        ?string $position = null,
        ?string $phone = null,
        ?string $bio = null,
        ?string $specialization = null,
    ): Mentor {
        return DB::transaction(function () use ($userId, $type, $employeeId, $companyName, $position, $phone, $bio, $specialization) {
            $user = User::findOrFail($userId);
            $user->assignRole('supervisor');

            return Mentor::create([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'type' => $type,
                'employee_id' => $employeeId,
                'company_name' => $companyName,
                'position' => $position,
                'phone' => $phone,
                'bio' => $bio,
                'specialization' => $specialization,
                'is_active' => true,
            ]);
        });
    }
}
