<?php

declare(strict_types=1);

namespace App\Domain\Mentee\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\User\Models\User;
use App\Domain\User\Rules\SystemUsername;
use App\Domain\User\Support\UserIdentifierGenerator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateMenteeAction extends BaseAction
{
    public function execute(array $userData, array $menteeData = []): Mentee
    {
        $userData['username'] = $userData['username'] ?? UserIdentifierGenerator::generateUsername();

        Validator::make($userData, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'unique:users,username', new SystemUsername],
            'email' => ['required', 'email', 'unique:users,email'],
        ])->validate();

        return $this->transaction(function () use ($userData, $menteeData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'username' => $userData['username'],
                'password' => Hash::make($userData['password'] ?? str()->random(12)),
                'setup_required' => $userData['setup_required'] ?? false,
            ]);

            $user->assignRole('student');

            $mentee = Mentee::create(array_merge(
                $menteeData,
                ['user_id' => $user->id],
            ));

            $this->log('mentee_created', $mentee, [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return $mentee;
        });
    }
}
