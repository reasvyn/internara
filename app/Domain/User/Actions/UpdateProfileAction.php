<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Auth\Entities\SuperAdminIntegrityRules;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * S1 - Secure: Atomic profile updates with auditing and validation.
 * S2 - Sustain: Proper error handling and logging.
 */
class UpdateProfileAction extends BaseAction
{
    /**
     * Execute the profile update.
     *
     * @param array<string, mixed> $data
     *
     * @throws RuntimeException when update fails
     */
    public function execute(User $user, array $data, ?string $name = null, ?string $email = null, ?UploadedFile $avatar = null): Profile
    {
        $integrity = SuperAdminIntegrityRules::fromModel($user);

        if ($name !== null && ! $integrity->canChangeName()) {
            throw new RejectedException('Cannot change super admin name.');
        }

        $this->validate($data);

        $data = array_filter($data, fn ($v) => $v !== null);

        return $this->withErrorHandling(function () use ($user, $data, $name, $email, $avatar) {
            return DB::transaction(function () use ($user, $data, $name, $email, $avatar) {
                if ($name !== null || $email !== null) {
                    $userData = [];
                    if ($name !== null) {
                        $userData['name'] = $name;
                    }
                    if ($email !== null) {
                        $userData['email'] = $email;
                    }
                    $user->update($userData);
                }

                if ($avatar !== null) {
                    $user->addMedia($avatar)->toMediaCollection('avatar');
                }

                if ($data === []) {
                    return $user->profile ?? $user->profile()->create([]);
                }

                $profile = $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $data,
                );

                SmartLogger::info('profile_updated')
                    ->event('profile_updated')
                    ->module('Profile')
                    ->about($profile)
                    ->withPayload(array_keys($data))
                    ->activityOnly()
                    ->save();

                return $profile;
            });
        }, 'Failed to update profile');
    }

    /**
     * Validate profile data.
     *
     * @param array<string, mixed> $data
     */
    protected function validate(array $data): void
    {
        Validator::make($data, [
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'gender' => ['nullable', 'string'],
            'blood_type' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_address' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string'],
            'national_id_number' => ['nullable', 'string', 'max:50'],
            'student_id_number' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employment_status' => ['nullable', 'string'],
            'nip' => ['nullable', 'string', 'max:18'],
            'nuptk' => ['nullable', 'string', 'max:16'],
            'competence_field' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string'],
        ])->validate();
    }
}
