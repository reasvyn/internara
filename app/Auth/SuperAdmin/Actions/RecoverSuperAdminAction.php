<?php

declare(strict_types=1);

namespace App\Auth\SuperAdmin\Actions;

use App\Auth\Permissions\Enums\Role;
use App\Auth\SuperAdmin\Entities\SuperAdminIntegrityRules;
use App\Auth\SuperAdmin\Events\SuperAdminRecovered;
use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class RecoverSuperAdminAction extends BaseCommandAction
{
    public function execute(string $email, string $password): User
    {
        $cacheKey = config('cache-keys.recover_admin_attempts').md5($email);
        $attempts = (int) Cache::get($cacheKey, 0);

        if ($attempts >= 3) {
            throw new RejectedException('Too many recovery attempts. Try again in 15 minutes.');
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

            event(new SuperAdminRecovered($user, $email));

            Cache::forget($cacheKey);

            return $user;
        });
    }
}
