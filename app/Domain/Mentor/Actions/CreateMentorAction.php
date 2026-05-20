<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Actions;

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Mentor\Models\Mentor;
use App\Domain\User\Models\User;
use App\Domain\User\Rules\SystemUsername;
use App\Domain\User\Support\UserIdentifierGenerator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateMentorAction extends BaseAction
{
    public function execute(array $userData, array $mentorData, ?string $role = null): Mentor
    {
        $mentorData['type'] ??= Mentor::TYPE_SCHOOL_TEACHER;
        $role ??= match ($mentorData['type']) {
            Mentor::TYPE_INDUSTRY_SUPERVISOR => Role::SUPERVISOR->value,
            default => Role::TEACHER->value,
        };

        $userData['username'] = $userData['username'] ?? UserIdentifierGenerator::generateUsername();

        Validator::make($userData, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'unique:users,username', new SystemUsername],
            'email' => ['required', 'email', 'unique:users,email'],
        ])->validate();

        return $this->transaction(function () use ($userData, $mentorData, $role) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'username' => $userData['username'],
                'password' => Hash::make($userData['password'] ?? str()->random(12)),
                'setup_required' => $userData['setup_required'] ?? false,
            ]);

            $user->assignRole($role);

            $mentor = Mentor::create(array_merge(
                $mentorData,
                ['user_id' => $user->id],
            ));

            $this->log('mentor_created', $mentor, [
                'user_id' => $user->id,
                'email' => $user->email,
                'type' => $mentor->type,
                'role' => $role,
            ]);

            return $mentor;
        });
    }
}
