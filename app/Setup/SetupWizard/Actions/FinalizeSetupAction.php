<?php

declare(strict_types=1);

namespace App\Setup\SetupWizard\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Contracts\SendsNotifications;
use App\Core\Exceptions\RejectedException;
use App\Setup\Entities\SetupEntity;
use App\Setup\SetupWizard\Events\SetupFinalized;
use App\User\UserManagement\Actions\SaveRecoveryKeyAction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

final class FinalizeSetupAction extends BaseCommandAction
{
    public function __construct(
        protected readonly SetupSchoolAction $setupSchool,
        protected readonly SetupDepartmentAction $setupDept,
        protected readonly SetupSuperAdminAction $setupAdmin,
        protected readonly SendsNotifications $sendNotification,
        protected readonly SaveRecoveryKeyAction $saveRecoveryKey,
    ) {}

    public function execute(
        array $schoolData,
        array $departmentData,
        array $adminData,
        array $stepsToComplete = ['account', 'school', 'department'],
    ): string {
        $state = SetupEntity::get();

        if ($state->isInstalled()) {
            throw new RejectedException('System is already installed.');
        }

        $result = $this->transaction(function () use (
            $schoolData,
            $departmentData,
            $adminData,
            $stepsToComplete,
            $state,
        ) {
            $this->setupSchool->execute($schoolData);

            $department = $this->setupDept->execute($departmentData);

            $admin = $this->setupAdmin->execute($adminData['email'], $adminData['password']);

            $completedSteps = $state->completedSteps();

            foreach ($stepsToComplete as $step) {
                if (! in_array($step, $completedSteps)) {
                    $completedSteps[] = $step;
                }
            }

            $keyLength = (int) config('setup.recovery_key.length', 64);
            $plaintext = Str::random($keyLength);
            $hashed = Hash::make($plaintext);

            SetupEntity::update([
                'is_installed' => true,
                'completed_steps' => $completedSteps,
                'install_token' => null,
                'token_expires_at' => null,
                'install_recovery_key' => $hashed,
                'updated_at' => now()->toIso8601String(),
            ]);

            $this->dispatchEvent(
                new SetupFinalized(
                    departmentId: $department->id,
                    installedAt: now()->toDateTimeImmutable(),
                ),
            );

            return [
                'plaintext' => $plaintext,
                'departmentId' => $department->id,
                'adminId' => $admin->id,
            ];
        });

        $this->sendNotification->execute(
            userId: $result['adminId'],
            type: 'system',
            title: __('notifications.system_installed.title'),
            message: __('notifications.system_installed.message'),
            link: route('sysadmin.dashboard'),
        );

        Session::forget([
            'setup.authorized',
            'setup.token',
            'setup.token_input',
            'setup.form_data',
        ]);

        try {
            $this->saveRecoveryKey->execute($result['plaintext']);
        } catch (\Throwable $e) {
            $this->log('recovery_key.file_save_failed', null, [
                'error' => $e->getMessage(),
            ]);
        }

        return $result['plaintext'];
    }
}
