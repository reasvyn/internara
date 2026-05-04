<?php

declare(strict_types=1);

namespace App\Livewire\Setup;

use App\Domain\Core\Support\AppInfo;
use App\Domain\School\Actions\SetupDepartmentAction;
use App\Domain\School\Actions\SetupSchoolAction;
use App\Domain\Setup\Exceptions\SetupException;
use App\Domain\Setup\Exceptions\SetupExceptionRenderer;
use App\Domain\Setup\Services\EnvAuditor;
use App\Domain\Setup\Services\SetupService;
use App\Domain\User\Actions\SetupSuperAdminAction;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

/**
 * Setup Wizard Livewire Component.
 * Orchestrates the system installation steps.
 *
 * S1 - Secure: Multi-step validation, session-based state, admin creation.
 * S2 - Sustain: Modern UI via Mary UI, clear progression logic.
 */
#[Layout('layouts.auth')]
class SetupWizard extends Component
{
    use Toast;

    public int $currentStep = 1;

    // Step 1: Welcome
    public array $audit = [];

    public bool $auditPassed = false;

    // Step 2: School
    public string $schoolName = '';

    public string $institutionalCode = '';

    public string $schoolEmail = '';

    // Step 3: Department
    public string $departmentName = '';

    // Step 4: Admin Account
    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminPassword = '';

    public string $adminPasswordConfirmation = '';

    public string $adminUsername = ''; // To display after generation

    // Step 5: Internship (Simplified for now)
    public string $internshipName = '';

    public string $startDate = '';

    public string $endDate = '';

    // Step 6: Finalize
    public bool $databaseAware = false;

    public bool $securityAware = false;

    protected SetupService $setupService;

    public function boot(SetupService $setupService): void
    {
        $this->setupService = $setupService;
    }

    public function mount(): void
    {
        if ($this->setupService->isInstalled()) {
            $this->redirect(route('login'));

            return;
        }

        $this->runAudit(app(EnvAuditor::class));
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
            'schoolEmail' => $this->schoolEmail,
            'departmentName' => $this->departmentName,
            'adminName' => $this->adminName,
            'adminEmail' => $this->adminEmail,
            'internshipName' => $this->internshipName,
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

    public function runAudit(EnvAuditor $auditor): void
    {
        $this->audit = $auditor->audit();
        $this->auditPassed = $this->audit['passed'];
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
                'adminPassword' => 'required|string|min:8',
                'adminPasswordConfirmation' => 'required|same:adminPassword',
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

    public function finish(
        SetupSchoolAction $setupSchool,
        SetupDepartmentAction $setupDept,
        SetupSuperAdminAction $setupAdmin
    ): void {
        $this->validate([
            'databaseAware' => 'accepted',
            'securityAware' => 'accepted',
        ]);

        try {
            // 1. Setup School
            $school = $setupSchool->execute([
                'name' => $this->schoolName,
                'institutional_code' => $this->institutionalCode,
                'email' => $this->schoolEmail,
            ]);

            // 2. Setup Department
            $department = $setupDept->execute($school->id, [
                'name' => $this->departmentName,
            ]);

            // 3. Setup Super Admin
            $admin = $setupAdmin->execute([
                'name' => $this->adminName,
                'email' => $this->adminEmail,
                'password' => $this->adminPassword,
            ]);

            $this->adminUsername = $admin->username;

            // 4. Finalize Setup Service
            $this->setupService->completeStep('school', ['school_id' => $school->id]);
            $this->setupService->completeStep('department', ['department_id' => $department->id]);
            $this->setupService->completeStep('account', ['admin_id' => $admin->id]);

            $this->setupService->finalize();

            $this->currentStep = 7;
            $this->success('System installed successfully!');
        } catch (SetupException $e) {
            SetupExceptionRenderer::handle($this, $e);
        } catch (\Exception $e) {
            logger()->error('Setup Failed: '.$e->getMessage());
            $this->error('Installation failed: '.$e->getMessage());
        }
    }

    public function goToStep(string $stepKey): void
    {
        $steps = array_flip(SetupService::STEPS);
        $targetStep = $steps[$stepKey] ?? 1;

        if ($targetStep < $this->currentStep || $this->setupService->isStepCompleted($stepKey)) {
            $this->currentStep = $targetStep;
        }
    }

    public function render()
    {
        return view('livewire.setup.setup-wizard', [
            'appName' => AppInfo::get('name', config('app.name')),
            'appVersion' => AppInfo::version(),
        ]);
    }
}
