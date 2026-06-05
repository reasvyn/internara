<?php

declare(strict_types=1);

namespace App\Setup\Actions;

use App\Academics\Events\SetupFinalized;
use App\Core\Actions\BaseAction;
use App\Core\Contracts\SendsNotifications;
use App\Core\Support\SmartLogger;
use App\Program\Internship\Actions\CreateInternshipAction;
use App\Setup\Models\Setup;
use App\Support\CacheKeys;
use App\SysAdmin\Account\Actions\SaveRecoveryKeyAction;
use App\User\SuperAdmin\Actions\SetupSuperAdminAction;
use Illuminate\Support\Facades\Cache;
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
        protected readonly CreateInternshipAction $createInternship,
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
        $existing = Setup::latest('created_at')->first();

        if ($existing && $existing->is_installed) {
            throw new RuntimeException('System is already installed.');
        }

        $plaintext = $this->transaction(function () use ($schoolData, $departmentData, $adminData, $internshipData, $stepsToComplete) {
            $setup = Setup::lockForUpdate()->latest('created_at')->first() ?? new Setup;

            if ($setup->exists && $setup->is_installed) {
                throw new RuntimeException('System is already installed.');
            }

            $school = $this->setupSchool->execute($schoolData);

            $department = $this->setupDept->execute($school->id, $departmentData);

            $admin = $this->setupAdmin->execute($adminData['email'], $adminData['password']);

            if ($internshipData !== null) {
                $this->createInternship->execute($internshipData);
            }
            $completedSteps = $setup->completed_steps ?? [];

            foreach ($stepsToComplete as $step) {
                if (! in_array($step, $completedSteps)) {
                    $completedSteps[] = $step;
                }
            }

            $keyLength = (int) config('setup.recovery_key.length', 64);
            $plaintext = Str::random($keyLength);
            $hashed = Hash::make($plaintext);

            $setup->fill([
                'is_installed' => true,
                'completed_steps' => $completedSteps,
                'setup_token' => null,
                'token_expires_at' => null,
                'recovery_key' => $hashed,
                'school_id' => $school->id,
                'department_id' => $department->id,
            ])->save();

            Cache::forget(CacheKeys::SETUP_INSTALLED);

            Event::dispatch(new SetupFinalized(
                schoolId: $setup->school_id,
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
