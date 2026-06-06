<?php

declare(strict_types=1);

namespace App\Setup\SetupWizard\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Contracts\SendsNotifications;
use App\Core\Support\SmartLogger;
use App\Settings\Support\Settings;
use App\Setup\SetupWizard\Entities\SetupState;
use App\Setup\SetupWizard\Events\SetupFinalized;
use App\SysAdmin\Account\Actions\SaveRecoveryKeyAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use RuntimeException;

final class FinalizeSetupAction extends BaseAction
{
    public function __construct(
        protected readonly SetupSchoolAction $setupSchool,
        protected readonly SetupDepartmentAction $setupDept,
        protected readonly SetupSuperAdminAction $setupAdmin,
        protected readonly SetupInternshipAction $setupInternship,
        protected readonly SendsNotifications $sendNotification,
        protected readonly SaveRecoveryKeyAction $saveRecoveryKey,
    ) {}

    public function execute(
        array $schoolData,
        array $departmentData,
        array $adminData,
        ?array $internshipData = null,
        array $stepsToComplete = ['account', 'school', 'department'],
    ): string {
        $state = SetupState::fromSettings();

        if ($state->isInstalled()) {
            throw new RuntimeException('System is already installed.');
        }

        $plaintext = $this->transaction(function () use ($schoolData, $departmentData, $adminData, $internshipData, $stepsToComplete) {
            $state = SetupState::fromSettings();

            if ($state->isInstalled()) {
                throw new RuntimeException('System is already installed.');
            }

            $this->setupSchool->execute($schoolData);

            $department = $this->setupDept->execute($departmentData);

            $admin = $this->setupAdmin->execute($adminData['email'], $adminData['password']);

            if ($internshipData !== null) {
                $this->setupInternship->execute($internshipData);
            }

            $completedSteps = $state->completedSteps();

            foreach ($stepsToComplete as $step) {
                if (! in_array($step, $completedSteps)) {
                    $completedSteps[] = $step;
                }
            }

            $keyLength = (int) config('setup.recovery_key.length', 64);
            $plaintext = Str::random($keyLength);
            $hashed = Hash::make($plaintext);

            Settings::set([
                'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
                'setup.completed_steps' => ['value' => $completedSteps, 'group' => 'setup', 'type' => 'json'],
                'setup.install_token' => ['value' => null, 'group' => 'setup', 'type' => 'string'],
                'setup.token_expires_at' => ['value' => null, 'group' => 'setup', 'type' => 'datetime'],
                'setup.install_recovery_key' => ['value' => $hashed, 'group' => 'setup', 'type' => 'string'],
                'setup.updated_at' => ['value' => now()->toIso8601String(), 'group' => 'setup', 'type' => 'datetime'],
            ]);

            Event::dispatch(new SetupFinalized(
                departmentId: $department->id,
                installedAt: now()->toDateTimeImmutable(),
            ));

            $this->sendNotification->execute(
                userId: $admin->id,
                type: 'system',
                title: __('notifications.system_installed.title'),
                message: __('notifications.system_installed.message'),
                link: route('sysadmin.dashboard'),
            );

            Session::forget(['setup.authorized', 'setup.token', 'setup.token_input', 'setup.form_data']);

            return $plaintext;
        });

        try {
            $this->saveRecoveryKey->execute($plaintext);
        } catch (\Throwable) {
            SmartLogger::warning('Failed to save recovery key file')
                ->module('setup')
                ->event('recovery_key.file_save_failed')
                ->save();
        }

        return $plaintext;
    }
}
