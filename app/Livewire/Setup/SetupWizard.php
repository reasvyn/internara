<?php

declare(strict_types=1);

namespace App\Livewire\Setup;

use App\Actions\Internship\CreateInternshipAction;
use App\Actions\Notification\SendNotificationAction;
use App\Actions\School\SetupDepartmentAction;
use App\Actions\School\SetupSchoolAction;
use App\Actions\Setup\FinalizeSetupAction;
use App\Actions\User\SetupSuperAdminAction;
use App\Models\Setup;
use App\Services\Setup\EnvironmentAuditor;
use App\Support\AppInfo;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::setup')]
class SetupWizard extends Component
{
    public const array STEP_KEYS = ['welcome', 'school', 'department', 'account', 'internship', 'finalize', 'complete'];

    public int $currentStep = 1;

    // Step 1: Welcome
    public array $audit = [];

    public bool $auditPassed = false;

    // Step 2: School
    public string $schoolName = '';

    public string $institutionalCode = '';

    public string $schoolAddress = '';

    public string $schoolEmail = '';

    public string $schoolPhone = '';

    public string $schoolWebsite = '';

    public string $principalName = '';

    // Step 3: Department
    public string $departmentName = '';

    public string $departmentDescription = '';

    // Step 4: Admin Account
    public string $adminName = 'Administrator';

    public string $adminUsername = 'administrator';

    public string $adminEmail = '';

    public string $adminPassword = '';

    public string $adminPassword_confirmation = '';

    // Step 5: Internship
    public string $internshipName = '';

    public string $internshipDescription = '';

    public string $startDate = '';

    public string $endDate = '';

    // Step 6: Finalize
    public bool $dataVerified = false;

    public bool $securityAware = false;

    public function mount(): void
    {
        if (Setup::state()->isInstalled()) {
            $this->redirect(route('login'));

            return;
        }

        $this->runAudit(app(EnvironmentAuditor::class));
        $this->restoreState();
    }

    public function updated(string $property): void
    {
        $this->saveState();
    }

    protected function saveState(): void
    {
        session()->put('setup.form_data', [
            'schoolName' => $this->schoolName,
            'institutionalCode' => $this->institutionalCode,
            'schoolAddress' => $this->schoolAddress,
            'schoolEmail' => $this->schoolEmail,
            'schoolPhone' => $this->schoolPhone,
            'schoolWebsite' => $this->schoolWebsite,
            'principalName' => $this->principalName,
            'departmentName' => $this->departmentName,
            'departmentDescription' => $this->departmentDescription,
            'adminName' => $this->adminName,
            'adminUsername' => $this->adminUsername,
            'adminEmail' => $this->adminEmail,
            'internshipName' => $this->internshipName,
            'internshipDescription' => $this->internshipDescription,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    protected function restoreState(): void
    {
        $data = session()->get('setup.form_data', []);

        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function runAudit(EnvironmentAuditor $auditor): void
    {
        $report = $auditor->audit();

        $categories = [];
        foreach ($report->checks as $check) {
            $categoryKey = $check->category->value;

            if (! isset($categories[$categoryKey])) {
                $categories[$categoryKey] = [
                    'label' => $check->category->label(),
                    'checks' => [],
                ];
            }

            $categories[$categoryKey]['checks'][] = [
                'name' => $check->nameKey,
                'status' => $check->status->value,
                'message' => $check->messageKey,
                'name_params' => $check->nameParams,
                'message_params' => $check->messageParams,
            ];
        }

        $this->audit = ['categories' => $categories];
        $this->auditPassed = $report->passed();
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1 && ! $this->auditPassed) {
            flash()->error(__('setup.wizard.audit_must_pass'));

            return;
        }

        if ($this->currentStep === 2) {
            $this->validate([
                'schoolName' => 'required|string|max:255',
                'institutionalCode' => 'required|string|max:50',
                'schoolEmail' => 'required|email|max:255',
            ]);
        }

        if ($this->currentStep === 3) {
            $this->validate([
                'departmentName' => 'required|string|max:255',
            ]);
        }

        if ($this->currentStep === 4) {
            $this->validate([
                'adminEmail' => 'required|email|max:255',
                'adminPassword' => 'required|string|min:8|confirmed',
            ]);
        }

        if ($this->currentStep === 5) {
            $this->validate([
                'internshipName' => 'required|string|max:255',
                'startDate' => 'required|date',
                'endDate' => 'required|date|after:startDate',
            ]);
        }

        $this->currentStep++;
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep(string $stepKey): void
    {
        $stepIndex = array_search($stepKey, self::STEP_KEYS, true);

        if ($stepIndex === false) {
            return;
        }

        $targetStep = $stepIndex + 1;

        if ($targetStep < $this->currentStep || Setup::state()->isStepCompleted($stepKey)) {
            $this->currentStep = $targetStep;
        }
    }

    public function finish(
        SetupSchoolAction $setupSchool,
        SetupDepartmentAction $setupDept,
        SetupSuperAdminAction $setupAdmin,
        CreateInternshipAction $createInternship,
        FinalizeSetupAction $finalizeSetup,
        SendNotificationAction $sendNotification
    ): void {
        $this->validate([
            'dataVerified' => 'accepted',
            'securityAware' => 'accepted',
        ]);

        try {
            // 1. Setup School
            $school = $setupSchool->execute([
                'name' => $this->schoolName,
                'institutional_code' => $this->institutionalCode,
                'address' => $this->schoolAddress ?: '-',
                'email' => $this->schoolEmail ?: null,
                'phone' => $this->schoolPhone ?: null,
                'website' => $this->schoolWebsite ?: null,
                'principal_name' => $this->principalName ?: null,
            ]);

            // 2. Setup Department
            $department = $setupDept->execute($school->id, [
                'name' => $this->departmentName,
                'description' => $this->departmentDescription ?: null,
            ]);

            // 3. Setup Super Admin
            $admin = $setupAdmin->execute([
                'name' => $this->adminName,
                'email' => $this->adminEmail,
                'username' => $this->adminUsername,
                'password' => $this->adminPassword,
            ]);

            // 4. Create initial internship
            if ($this->internshipName) {
                $createInternship->execute([
                    'name' => $this->internshipName,
                    'description' => $this->internshipDescription ?: null,
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                    'status' => 'draft',
                ]);
            }

            // 5. Finalize (marks steps completed, installs, generates recovery key)
            $finalizeSetup->execute(['school', 'department', 'account']);

            // 6. Notify admin that the system is installed
            $sendNotification->execute(
                userId: $admin->id,
                type: 'system',
                title: __('notifications.system_installed.title'),
                message: __('notifications.system_installed.message'),
                link: route('admin.dashboard'),
            );

            $this->currentStep = 7;
            flash()->success(__('setup.wizard.setup_complete'));
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
            logger()->error('Setup error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        } catch (\Exception $e) {
            logger()->error('Setup Failed: '.$e->getMessage());
            flash()->error(__('setup.wizard.install_failed', ['message' => $e->getMessage()]));
        }
    }

    public function finishSession(): void
    {
        $this->redirect(route('login'));
    }

    public function render()
    {
        return view('livewire.setup.setup-wizard', [
            'appName' => AppInfo::get('name', config('app.name')),
            'appVersion' => AppInfo::version(),
            'stepKeys' => self::STEP_KEYS,
        ]);
    }
}
