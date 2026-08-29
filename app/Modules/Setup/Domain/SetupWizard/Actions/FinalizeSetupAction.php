<?php

declare(strict_types=1);

namespace App\Modules\Setup\Domain\SetupWizard\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Settings\Actions\BatchSetSettingAction;
use App\Modules\Settings\Data\SettingEntryData;
use App\Modules\Setup\Entities\SetupEntity;
use App\Modules\Setup\Domain\SetupWizard\Data\FinalizeSetupData;
use App\Modules\Setup\Domain\SetupWizard\Events\SetupFinalized;
use App\Modules\User\Domain\UserManagement\Actions\SaveRecoveryKeyAction;
use Illuminate\Support\Facades\Cache;
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
        protected readonly BatchSetSettingAction $batchSetSetting,
    ) {}

    public function execute(FinalizeSetupData $data): string
    {
        $state = SetupEntity::get();

        if ($state->isInstalled()) {
            throw new RejectedException(__('setup.already_installed'));
        }

        $result = $this->transaction(function () use ($data, $state) {
            $this->setupSchool->execute($data->schoolData);

            $department = $this->setupDept->execute($data->departmentData);

            $admin = $this->setupAdmin->execute($data->adminData['email'], $data->adminData['password']);

            $completedSteps = $state->completedSteps();

            foreach ($data->stepsToComplete as $step) {
                if (! in_array($step, $completedSteps)) {
                    $completedSteps[] = $step;
                }
            }

            $keyLength = (int) config('setup.recovery_key.length', 64);
            $plaintext = Str::random($keyLength);
            $hashed = Hash::make($plaintext);

            $schoolName = $data->schoolData['name'] ?? config('app.name', 'Internara');

            $this->batchSetSetting->execute(
                ...[
                    ...SetupEntity::toSettingsEntries([
                        'is_installed' => true,
                        'completed_steps' => $completedSteps,
                        'install_token' => null,
                        'token_expires_at' => null,
                        'install_recovery_key' => $hashed,
                        'updated_at' => now()->toIso8601String(),
                    ]),
                    new SettingEntryData(
                        key: 'brand_name',
                        value: $schoolName,
                        group: 'general',
                        type: 'string',
                    ),
                    new SettingEntryData(
                        key: 'site_title',
                        value: "{$schoolName} — Vocational Fieldwork Management System",
                        group: 'general',
                        type: 'string',
                    ),
                ],
            );

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

        Cache::forget(config('cache-keys.setup_installed'));

        $this->sendNotification->execute(new NotificationData(
            userId: $result['adminId'],
            type: 'system',
            title: __('notifications.system_installed.title'),
            message: __('notifications.system_installed.message'),
            link: route('sysadmin.dashboard'),
        ));

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
