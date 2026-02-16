<?php

declare(strict_types=1);

namespace Modules\Profile\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Permission\Enums\Role;
use Modules\Profile\Models\Profile;
use Modules\Profile\Services\Contracts\ProfileService as Contract;
use Modules\Shared\Services\EloquentQuery;
use Modules\Student\Services\Contracts\StudentService;
use Modules\Teacher\Services\Contracts\TeacherService;

/**
 * @property Profile $model
 */
class ProfileService extends EloquentQuery implements Contract
{
    public function __construct(
        Profile $model,
        protected StudentService $studentService,
        protected TeacherService $teacherService,
    ) {
        $this->setModel($model);
    }

    /**
     * Define the HasOne relationship for the User model.
     */
    public function defineHasOne(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): HasOne {
        return $related->hasOne(Profile::class, $foreignKey ?: 'user_id', $localKey);
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
    public function syncProfileable(Profile $profile, array $roles, array $extraData = []): Profile
    {
        if ($profile->profileable_id) {
            // Optional: update extra data if needed even if exists
            return $profile;
        }

        if (in_array(Role::STUDENT->value, $roles)) {
            $student = $this->studentService->createWithDefault($extraData);
            $profile->profileable()->associate($student);
            $profile->save();
        } elseif (in_array(Role::TEACHER->value, $roles)) {
            $teacher = $this->teacherService->createWithDefault($extraData);
            $profile->profileable()->associate($teacher);
            $profile->save();
        }

        return $profile;
    }
}
