<?php

declare(strict_types=1);

namespace Modules\Profile\Services;

use Modules\Profile\Models\Profile;
use Modules\Profile\Services\Contracts\ProfileService as Contract;
use Modules\Shared\Services\EloquentQuery;

/**
 * @property Profile $model
 */
class ProfileService extends EloquentQuery implements Contract
{
    public function __construct(Profile $model)
    {
        $this->setModel($model);
    }

    /**
     * Get or create a profile for a specific user.
     */
    public function getByUserId(string $userId): Profile
    {
        /** @var Profile */
        return $this->model->newQuery()->firstOrCreate(['user_id' => $userId]);
    }

    /**
     * Synchronize the profileable model based on user roles.
     */
    public function syncProfileable(Profile $profile, array $roles): Profile
    {
        if ($profile->profileable_id) {
            return $profile;
        }

        if (in_array('student', $roles)) {
            $student = \Modules\Student\Models\Student::create(['nisn' => 'PENDING-'.(string) \Illuminate\Support\Str::uuid()]);
            $profile->profileable()->associate($student);
            $profile->save();
        } elseif (in_array('teacher', $roles)) {
            $teacher = \Modules\Teacher\Models\Teacher::create(['nip' => 'PENDING-'.(string) \Illuminate\Support\Str::uuid()]);
            $profile->profileable()->associate($teacher);
            $profile->save();
        }

        return $profile;
    }
}
