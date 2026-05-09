<?php

declare(strict_types=1);

namespace App\Actions\Mentee;

use App\Actions\Core\LogAuditAction;
use App\Models\Mentee;
use App\Models\User;
use App\Rules\User\SystemUsername;
use App\Support\User\UserIdentifierGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateMenteeAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $userData, array $menteeData = []): Mentee
    {
        $userData['username'] = $userData['username'] ?? UserIdentifierGenerator::generateUsername();

        Validator::make($userData, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'unique:users,username', new SystemUsername],
            'email' => ['required', 'email', 'unique:users,email'],
        ])->validate();

        return DB::transaction(function () use ($userData, $menteeData) {
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

            $this->logAudit->execute(
                action: 'mentee_created',
                subjectType: Mentee::class,
                subjectId: $mentee->id,
                payload: [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ],
                module: 'Mentee',
            );

            return $mentee;
        });
    }
}
