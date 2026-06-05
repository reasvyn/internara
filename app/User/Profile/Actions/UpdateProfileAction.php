<?php

declare(strict_types=1);

namespace App\User\Profile\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Core\Support\SmartLogger;
use App\User\Profile\Models\Profile;
use App\User\SuperAdmin\Entities\SuperAdminIntegrityRules;
use App\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * S1 - Secure: Atomic profile updates with auditing and validation.
 * S2 - Sustain: Proper error handling and logging.
 */
final class UpdateProfileAction extends BaseAction
{
    /**
     * Execute the profile update.
     *
     * @param array<string, mixed> $data
     *
     * @throws RuntimeException when update fails
     */
    public function execute(User $user, array $data, ?string $name = null, ?string $email = null, ?UploadedFile $avatar = null, ?string $username = null): Profile
    {
        $integrity = SuperAdminIntegrityRules::fromModel($user);

        if ($name !== null && ! $integrity->canChangeName()) {
            throw new RejectedException('Cannot change super admin name.');
        }

        if ($username !== null && ! $integrity->canChangeUsername()) {
            throw new RejectedException('Cannot change super admin username.');
        }

        $userRules = [];
        $userData = [];
        if ($name !== null) {
            $userRules['name'] = ['required', 'string', 'max:255'];
            $userData['name'] = $name;
        }
        if ($email !== null) {
            $userRules['email'] = ['required', 'email', 'unique:users,email,'.$user->id];
            $userData['email'] = $email;
        }
        if ($username !== null) {
            $userRules['username'] = ['required', 'string', 'alpha_num', 'lowercase', 'max:50', 'unique:users,username,'.$user->id];
            $userData['username'] = $username;
        }

        if ($userRules !== []) {
            Validator::make($userData, $userRules)->validate();
        }

        $this->validate($data);

        $data = array_filter($data, fn ($v) => $v !== null);

        return DB::transaction(function () use ($user, $data, $userData, $avatar) {
            if ($userData !== []) {
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
            'employee_id_number' => ['nullable', 'string', 'max:30'],
            'educator_id_number' => ['nullable', 'string', 'max:30'],
            'competence_field' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string'],
        ])->validate();
    }
}
