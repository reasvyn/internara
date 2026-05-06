<?php

declare(strict_types=1);

namespace App\Livewire\Setup;

use App\Actions\School\SetupDepartmentAction;
use App\Actions\School\SetupSchoolAction;
use App\Actions\Setup\FinalizeSetupAction;
use App\Actions\User\SetupSuperAdminAction;
use App\Domain\Setup\Exceptions\SetupException;
use App\Domain\Setup\Exceptions\SetupExceptionRenderer;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Models\Setup;
use App\Support\AppInfo;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.auth')]
class SetupWizard extends Component
{
    use Toast;

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
    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminPassword = '';

    public string $adminPassword_confirmation = '';

    public string $adminUsername = '';

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
        if (Setup::isInstalled()) {
            $this->redirect(route('login'));

            return;
        }

        $this->runAudit(app(EnvironmentAuditor::class));
        $this->restoreState();
        $this->generateAdminUsername();
    }

    public function updated(string $property): void
    {
        if ($property === 'adminName') {
            $this->generateAdminUsername();
        }

        $this->saveState();
    }

    protected function generateAdminUsername(): void
    {
        if ($this->adminName !== '') {
            $this->adminUsername = strtolower(str_replace(' ', '', substr($this->adminName, 0, 20)));
        }
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
                'name' => $check->name,
                'status' => $check->status->value,
                'message' => $check->message,
            ];
        }

        $this->audit = ['categories' => $categories];
        $this->auditPassed = $report->passed();
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1 && ! $this->auditPassed) {
            $this->error('System audit failed. Please fix issues before proceeding.');

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
                'adminName' => 'required|string|max:255',
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

        if ($targetStep < $this->currentStep || Setup::isStepCompleted($stepKey)) {
            $this->currentStep = $targetStep;
        }
    }

    public function finish(
        SetupSchoolAction $setupSchool,
        SetupDepartmentAction $setupDept,
        SetupSuperAdminAction $setupAdmin,
        FinalizeSetupAction $finalizeSetup
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

            $this->adminUsername = $admin->username;

            // 4. Mark steps completed
            Setup::markStepCompleted('school');
            Setup::markStepCompleted('department');
            Setup::markStepCompleted('account');

            // 5. Finalize
            $finalizeSetup->execute();

            $this->currentStep = 7;
            $this->success('System installed successfully!');
        } catch (SetupException $e) {
            SetupExceptionRenderer::handle($this, $e);
        } catch (\Exception $e) {
            logger()->error('Setup Failed: '.$e->getMessage());
            $this->error('Installation failed: '.$e->getMessage());
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
