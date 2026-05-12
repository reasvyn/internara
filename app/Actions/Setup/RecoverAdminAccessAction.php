<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Core\LogAuditAction;
use App\Enums\Auth\AccountStatus;
use App\Models\User;
use App\Notifications\Auth\AdminRecoveredNotification;
use App\Support\SmartLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final readonly class RecoverAdminAccessAction
{
    public function __construct(
        protected readonly LogAuditAction $logAudit,
    ) {}

    public function execute(string $email, string $password, bool $isReset = false, string $role = 'super_admin'): User
    {
        return DB::transaction(function () use ($email, $password, $isReset, $role) {
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
                    'name' => 'Recovery Admin',
                    'email' => $email,
                    'password' => Hash::make($password),
                    'username' => $this->generateUsername(),
                ]);
                $user->profile()->create();
                $user->setStatus(AccountStatus::PROTECTED);
            }

            $user->syncRoles([$role]);

            // Invalidate remember-me tokens so existing sessions are no longer valid
            $user->forceFill(['remember_token' => Str::random(60)])->save();

            $hostname = gethostname();
            $serverIp = $_SERVER['SERVER_ADDR'] ?? $_SERVER['HOSTNAME'] ?? 'unknown';

            $this->logAudit->execute(
                user: null,
                action: 'admin_recovered',
                subjectType: User::class,
                subjectId: $user->id,
                payload: [
                    'type' => $isReset ? 'reset' : 'create',
                    'email' => $email,
                    'role' => $role,
                    'hostname' => $hostname,
                    'server_ip' => $serverIp,
                ],
                module: 'Setup',
                maskPii: true,
            );

            SmartLogger::info('admin_recovery_'.$user->id)
                ->module('Setup')
                ->event($isReset ? 'admin.recovered.reset' : 'admin.recovered.create')
                ->systemOnly()
                ->save();

            // Notify existing active admin users about the recovery
            $this->notifyExistingAdmins($user, $isReset);

            return $user;
        });
    }

    private function notifyExistingAdmins(User $recoveredUser, bool $isReset): void
    {
        try {
            $existingAdmins = User::role(['super_admin', 'admin'])
                ->where('id', '!=', $recoveredUser->id)
                ->get();

            if ($existingAdmins->isEmpty()) {
                return;
            }

            Notification::send($existingAdmins, new AdminRecoveredNotification(
                recoveredEmail: $recoveredUser->email,
                mode: $isReset ? 'reset' : 'create',
                initiatorHostname: gethostname(),
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
