<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Core\LogAuditAction;
use App\Enums\Auth\AccountStatus;
use App\Models\User;
use App\Notifications\Auth\SuperAdminRecoveredNotification;
use App\Support\SmartLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final readonly class RecoverSuperAdminAction
{
    public function __construct(
        protected readonly LogAuditAction $logAudit,
    ) {}

    public function execute(string $email, string $password, bool $isReset = false): User
    {
        return DB::transaction(function () use ($email, $password, $isReset) {
            if ($isReset) {
                $user = User::where('email', $email)->firstOrFail();

                $user->update([
                    'password' => Hash::make($password),
                    'locked_at' => null,
                    'locked_reason' => null,
                ]);
                $user->setStatus(AccountStatus::VERIFIED);
            } else {
                $defaultName = config('setup.defaults.recovery_admin_name', 'Recovery Admin');

                $user = User::create([
                    'name' => $defaultName,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'username' => $this->generateUsername(),
                ]);
                $user->profile()->create();
                $user->setStatus(AccountStatus::PROTECTED);
            }

            $user->syncRoles(['super_admin']);

            $user->forceFill(['remember_token' => Str::random(60)])->save();

            $this->logAudit->execute(
                user: null,
                action: 'super_admin_recovered',
                subjectType: User::class,
                subjectId: $user->id,
                payload: [
                    'type' => $isReset ? 'reset' : 'create',
                    'email' => $email,
                ],
                module: 'Setup',
                maskPii: true,
            );

            SmartLogger::info('super_admin_recovery_'.$user->id)
                ->module('setup')
                ->event($isReset ? 'super_admin.recovered.reset' : 'super_admin.recovered.create')
                ->systemOnly()
                ->save();

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
        } catch (\Throwable) {
            // Notification failure must not break the recovery flow
        }
    }

    private function generateUsername(): string
    {
        return 'admin_'.substr(md5((string) time()), 0, 8);
    }
}
