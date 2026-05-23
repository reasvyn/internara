<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Auth\Notifications\SuperAdminRecoveredNotification;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class RecoverSuperAdminAction extends BaseAction
{
    public function execute(string $email, string $password, bool $isReset = false): User
    {
        return $this->transaction(function () use ($email, $password, $isReset) {
            if ($isReset) {
                $user = User::where('email', $email)->firstOrFail();

                $user->update([
                    'password' => Hash::make($password),
                    'locked_at' => null,
                    'locked_reason' => null,
                ]);
                $user->setStatus(AccountStatus::VERIFIED);
            } else {
                $user = User::create([
                    'name' => config('setup.defaults.admin_name', 'Administrator'),
                    'email' => $email,
                    'password' => Hash::make($password),
                    'username' => $this->generateUsername(),
                ]);
                $user->profile()->create();
                $user->setStatus(AccountStatus::PROTECTED);
            }

            $user->syncRoles([Role::SUPER_ADMIN->value]);

            $user->forceFill(['remember_token' => Str::random(60)])->save();

            $this->log('super_admin_recovered', $user, [
                'type' => $isReset ? 'reset' : 'create',
                'email' => $email,
            ]);

            $this->notifyExistingSuperAdmins($user, $isReset);

            return $user;
        });
    }

    private function notifyExistingSuperAdmins(User $recoveredUser, bool $isReset): void
    {
        try {
            $existingAdmins = User::role('super_admin')
                ->where('id', '!=', $recoveredUser->id)
                ->get();

            if ($existingAdmins->isEmpty()) {
                return;
            }

            Notification::send($existingAdmins, new SuperAdminRecoveredNotification(
                recoveredEmail: $recoveredUser->email,
                mode: $isReset ? 'reset' : 'create',
            ));
        } catch (\Throwable $e) {
            SmartLogger::error('Failed to notify existing super admins about recovery')
                ->withPayload([
                    'recovered_user_id' => $recoveredUser->id,
                    'error' => $e->getMessage(),
                ])
                ->systemOnly()
                ->save();
        }
    }

    private function generateUsername(): string
    {
        return 'admin_'.Str::random(16);
    }
}
