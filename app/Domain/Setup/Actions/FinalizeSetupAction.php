<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Admin\Actions\SendNotificationAction;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Actions\CreateInternshipAction;
use App\Domain\Setup\Events\SetupFinalized;
use App\Domain\Setup\Models\Setup;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

final class FinalizeSetupAction extends BaseAction
{
    public function __construct(
        protected readonly SetupSchoolAction $setupSchool,
        protected readonly SetupDepartmentAction $setupDept,
        protected readonly SetupSuperAdminAction $setupAdmin,
        protected readonly CreateInternshipAction $createInternship,
        protected readonly SendNotificationAction $sendNotification,
    ) {}

    public function execute(
        array $schoolData,
        array $departmentData,
        array $adminData,
        ?array $internshipData = null,
        array $stepsToComplete = ['school', 'department', 'account'],
    ): string {
        return $this->transaction(function () use ($schoolData, $departmentData, $adminData, $internshipData, $stepsToComplete) {
            $school = $this->setupSchool->execute($schoolData);

            $department = $this->setupDept->execute($school->id, $departmentData);

            $admin = $this->setupAdmin->execute($adminData);

            if ($internshipData !== null) {
                $this->createInternship->execute($internshipData);
            }

            $setup = Setup::firstOrCreate([]);
            $completedSteps = $setup->completed_steps ?? [];

            foreach ($stepsToComplete as $step) {
                if (! in_array($step, $completedSteps)) {
                    $completedSteps[] = $step;
                }
            }

            $keyLength = (int) config('setup.recovery_key.length', 64);
            $plaintext = Str::random($keyLength);
            $hashed = Hash::make($plaintext);

            $setup->forceFill([
                'is_installed' => true,
                'completed_steps' => $completedSteps,
                'setup_token' => null,
                'token_expires_at' => null,
                'recovery_key' => $hashed,
            ])->save();

            Event::dispatch(new SetupFinalized(
                schoolId: $setup->school_id,
                installedAt: now()->toDateTimeImmutable(),
            ));

            $this->sendNotification->execute(
                userId: $admin->id,
                type: 'system',
                title: __('notifications.system_installed.title'),
                message: __('notifications.system_installed.message'),
                link: route('admin.dashboard'),
            );

            Session::forget(['setup.authorized', 'setup.token', 'setup.token_input', 'setup.form_data']);

            return $plaintext;
        });
    }
}
