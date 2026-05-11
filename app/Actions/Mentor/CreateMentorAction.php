<?php

declare(strict_types=1);

namespace App\Actions\Mentor;

use App\Actions\Core\LogAuditAction;
use App\Enums\Auth\Role;
use App\Models\Mentor;
use App\Models\User;
use App\Rules\User\SystemUsername;
use App\Support\User\UserIdentifierGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateMentorAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

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

        return DB::transaction(function () use ($userData, $mentorData, $role) {
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

            $this->logAudit->execute(
                action: 'mentor_created',
                subjectType: Mentor::class,
                subjectId: $mentor->id,
                payload: [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'type' => $mentor->type,
                    'role' => $role,
                ],
                module: 'Mentor',
            );

            return $mentor;
        });
    }
}
