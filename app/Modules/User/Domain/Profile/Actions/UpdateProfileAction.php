<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Profile\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use App\Modules\User\Domain\Profile\Data\UpdateProfileData;
use App\Modules\User\Domain\Profile\Events\ProfileUpdated;
use App\Modules\User\Domain\Profile\Models\Profile;
use Illuminate\Support\Facades\Validator;

final class UpdateProfileAction extends BaseCommandAction
{
    public function execute(UpdateProfileData $data): Profile
    {
        $user = User::findOrFail($data->userId);

        $integrity = $user->asSuperAdminIntegrityRules();

        if ($data->name !== null && ! $integrity->canChangeName()) {
            throw new RejectedException(__('profile.cannot_change_super_admin_name'));
        }

        if ($data->username !== null && ! $integrity->canChangeUsername()) {
            throw new RejectedException(__('profile.cannot_change_super_admin_username'));
        }

        $userRules = [];
        $userData = [];
        if ($data->name !== null) {
            $userRules['name'] = ['required', 'string', 'max:255'];
            $userData['name'] = $data->name;
        }
        if ($data->email !== null) {
            $userRules['email'] = ['required', 'email', 'unique:users,email,'.$user->id];
            $userData['email'] = $data->email;
        }
        if ($data->username !== null) {
            $userRules['username'] = [
                'required',
                'string',
                'alpha_num',
                'lowercase',
                'max:50',
                'unique:users,username,'.$user->id,
            ];
            $userData['username'] = $data->username;
        }

        if ($userRules !== []) {
            Validator::make($userData, $userRules)->validate();
        }

        $this->validateProfileData($data->profile);

        $profileData = array_filter($data->profile, fn ($v) => $v !== null);

        return $this->transaction(function () use ($user, $profileData, $userData, $data) {
            if ($userData !== []) {
                $user->update($userData);
            }

            if ($data->avatar !== null) {
                $user->addMedia($data->avatar)->toMediaCollection('avatar');
            }

            $profile = $user->profile()->updateOrCreate(['user_id' => $user->id], $profileData);

            $this->dispatchEvent(
                new ProfileUpdated(
                    profile: $profile,
                    previousEmail: $user->getOriginal('email'),
                    previousUsername: $user->getOriginal('username'),
                ),
            );

            $this->log('profile_updated', $profile, array_keys($profileData));

            return $profile;
        });
    }

    protected function validateProfileData(array $data): void
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
            'id_number' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employment_status' => ['nullable', 'string'],
            'competence_field' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string'],
        ])->validate();
    }
}
