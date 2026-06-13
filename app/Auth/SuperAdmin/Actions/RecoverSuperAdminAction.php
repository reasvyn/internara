<?php

declare(strict_types=1);

namespace App\Auth\SuperAdmin\Actions;

use App\Auth\Permissions\Enums\Role;
use App\Auth\SuperAdmin\Entities\SuperAdminIntegrityRules;
use App\Auth\SuperAdmin\Notifications\SuperAdminRecoveredNotification;
use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Core\Support\SmartLogger;
use App\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;

final class RecoverSuperAdminAction extends BaseAction
{
    public function execute(string $email, string $password): User
    {
        $cacheKey = config('cache-keys.recover_admin_attempts').md5($email);
        $attempts = (int) Cache::get($cacheKey, 0);

        if ($attempts >= 3) {
            throw new RuntimeException('Too many recovery attempts. Try again in 15 minutes.');
        }

        Cache::put($cacheKey, $attempts + 1, 900);

        return $this->transaction(function () use ($email, $password, $cacheKey) {
            $user = User::where('email', $email)->firstOrFail();

            $integrity = SuperAdminIntegrityRules::fromModel($user);

            if ($user->hasRole(Role::SUPER_ADMIN->value) && ! $integrity->hasProtectedStatus()) {
                throw new RejectedException(
                    'Super admin account integrity violation: expected PROTECTED status.',
                );
            }

            $user->update([
                'password' => Hash::make($password),
                'locked_at' => null,
                'locked_reason' => null,
            ]);

            $user->syncRoles([Role::SUPER_ADMIN->value]);

            $user->forceFill(['remember_token' => Str::random(60)])->save();

            $this->log('super_admin_recovered', $user, [
                'type' => 'reset',
                'email' => $email,
            ]);

            $this->notifyExistingSuperAdmins($user);

            Cache::forget($cacheKey);

            return $user;
        });
    }

    private function notifyExistingSuperAdmins(User $recoveredUser): void
    {
        try {
            $existingAdmins = User::role('super_admin')
                ->where('id', '!=', $recoveredUser->id)
                ->get();

            if ($existingAdmins->isEmpty()) {
                return;
            }

            Notification::send(
                $existingAdmins,
                new SuperAdminRecoveredNotification(
                    recoveredEmail: $recoveredUser->email,
                ),
            );
        } catch (\Throwable $e) {
            SmartLogger::error('Failed to notify existing super admins about recovery')
                ->withPayload([
                    'recovered_user_id' => $recoveredUser->id,
                    'error' => $e->getMessage(),
                ])
                ->withPiiMasking()
                ->systemOnly()
                ->save();
        }
    }
}
