<?php

declare(strict_types=1);

namespace App\User\SuperAdmin\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Support\SmartLogger;
use App\Exceptions\RejectedException;
use App\Support\CacheKeys;
use App\User\Enums\AccountStatus;
use App\User\Enums\Role;
use App\User\Models\User;
use App\User\SuperAdmin\Entities\SuperAdminIntegrityRules;
use App\User\SuperAdmin\Notifications\SuperAdminRecoveredNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;

final class RecoverSuperAdminAction extends BaseAction
{
    public function execute(string $email, string $password, bool $isReset = false): User
    {
        $cacheKey = CacheKeys::RECOVER_ADMIN_ATTEMPTS.md5($email);
        $attempts = (int) Cache::get($cacheKey, 0);

        if ($attempts >= 3) {
            throw new RuntimeException('Too many recovery attempts. Try again in 15 minutes.');
        }

        Cache::put($cacheKey, $attempts + 1, 900);

        return $this->transaction(function () use ($email, $password, $isReset, $cacheKey) {
            if ($isReset) {
                $user = User::where('email', $email)->firstOrFail();

                $integrity = SuperAdminIntegrityRules::fromModel($user);

                if ($user->hasRole(Role::SUPER_ADMIN->value) && ! $integrity->hasProtectedStatus()) {
                    throw new RejectedException('Super admin account integrity violation: expected PROTECTED status.');
                }

                $user->update([
                    'password' => Hash::make($password),
                    'locked_at' => null,
                    'locked_reason' => null,
                ]);
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

            Cache::forget($cacheKey);

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
