<?php

declare(strict_types=1);

namespace App\Livewire\Setup;

use App\Actions\Setup\SetupDepartmentAction;
use App\Actions\Setup\SetupInternshipAction;
use App\Actions\Setup\SetupSchoolAction;
use App\Actions\Setup\SetupSuperAdminAction;
use App\Models\Setup;
use App\Services\Setup\InstallationAuditor;
use App\Services\Setup\SetupService;
use App\Support\AppInfo;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Unified setup wizard with pre-flight checks, token security, and lock file guard.
 *
 * S1 - Secure: Token-protected, lock file gate, no DB dependency before installation.
 * S2 - Sustain: Session-based state, clear step progression.
 */
class SetupWizard extends Component
{
    public int $currentStep = 1;

    // Pre-flight audit results
    public ?array $auditResults = null;
    public bool $auditPassed = false;

    // Step 1: Welcome (pre-flight checks)
    // No form inputs needed

    // Step 2: School
    public string $schoolName = '';
    public string $schoolCode = '';
    public string $schoolAddress = '';
    public string $schoolEmail = '';
    public string $schoolPhone = '';
    public string $principalName = '';

    // Step 3: Account (Admin)
    public string $adminName = '';
    public string $adminEmail = '';
    public string $adminUsername = '';
    public string $adminPassword = '';
    public string $adminPassword_confirmation = '';

    // Step 4: Department
    public string $departmentName = '';

    // Step 5: Internship
    public string $internshipName = '';
    public string $startDate = '';
    public string $endDate = '';

    // Step 6: Finalize
    public bool $dataVerified = false;
    public bool $securityAware = false;

    public function mount(InstallationAuditor $auditor): void
    {
        // S1: Double-check lock file even if middleware was bypassed
        $setup = app(SetupService::class);

        if ($setup->isInstalled()) {
            $this->redirectRoute('login');
        }

        // Restore step from session
        $this->currentStep = $setup->getCurrentStep();

        // Run pre-flight audit on first visit
        $this->auditResults = $auditor->audit();
        $this->auditPassed = $this->auditResults['passed'];
    }

    public function runAudit(InstallationAuditor $auditor): void
    {
        $this->auditResults = $auditor->audit();
        $this->auditPassed = $this->auditResults['passed'];
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1 && ! $this->auditPassed) {
            return;
        }

        // Validate current step before proceeding (steps 2-4 need validation)
        if ($this->currentStep > 1 && $this->currentStep < 5) {
            $this->validateStep();
        }

        $setup = app(SetupService::class);
        $stepName = $this->getStepName($this->currentStep);
        $setup->completeStep($stepName);

        $this->currentStep++;
        $setup->setCurrentStep($this->currentStep);
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            app(SetupService::class)->setCurrentStep($this->currentStep);
        }
    }

    protected function validateStep(): void
    {
        $rules = match ($this->currentStep) {
            2 => [
                'schoolName' => 'required|string|max:255',
                'schoolCode' => 'required|string|max:64',
                'schoolAddress' => 'required|string|max:500',
            ],
            3 => [
                'adminName' => 'required|string|max:255',
                'adminEmail' => 'required|email|unique:users,email',
                'adminUsername' => 'required|string|min:4|unique:users,username',
                'adminPassword' => 'required|string|min:8|confirmed',
            ],
            4 => [
                'departmentName' => 'required|string|max:255',
            ],
            5 => [
                'internshipName' => 'required|string|max:255',
                'startDate' => 'required|date',
                'endDate' => 'required|date|after:startDate',
            ],
            default => [],
        };

        $this->validate($rules);
    }

    public function finish(
        SetupSchoolAction $setupSchool,
        SetupDepartmentAction $setupDepartment,
        SetupInternshipAction $setupInternship,
        SetupSuperAdminAction $setupSuperAdmin
    ): void {
        // Validate finalization requirements
        $this->validate([
            'dataVerified' => 'accepted',
            'securityAware' => 'accepted',
        ]);

        // Validate step 5 data (internship)
        $this->validate([
            'internshipName' => 'required|string|max:255',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
        ]);

        DB::transaction(function () use ($setupSchool, $setupDepartment, $setupInternship, $setupSuperAdmin) {
            // 1. Setup School (step 2)
            $school = $setupSchool->execute([
                'name' => $this->schoolName,
                'institutional_code' => $this->schoolCode,
                'address' => $this->schoolAddress,
                'email' => $this->schoolEmail ?: null,
                'phone' => $this->schoolPhone ?: null,
                'principal_name' => $this->principalName ?: null,
            ]);

            // 2. Setup Super Admin (step 3)
            $admin = $setupSuperAdmin->execute([
                'name' => $this->adminName,
                'email' => $this->adminEmail,
                'username' => $this->adminUsername,
                'password' => $this->adminPassword,
            ]);

            // 3. Setup Department (step 4)
            $department = $setupDepartment->execute([
                'name' => $this->departmentName,
                'school_id' => $school->id,
            ]);

            // 4. Setup Internship (step 5)
            $internship = $setupInternship->execute([
                'name' => $this->internshipName,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'status' => 'draft',
            ]);

            // 5. Create Setup audit record
            Setup::create([
                'version' => AppInfo::version(),
                'is_installed' => true,
                'admin_id' => $admin->id,
                'school_id' => $school->id,
                'department_id' => $department->id,
                'internship_id' => $internship->id,
                'completed_steps' => SetupService::STEPS,
            ]);

            // 6. Persist setup_completed setting
            app(\App\Actions\Setting\SetSettingAction::class)->execute('setup_completed', 'true', 'boolean', 'system');
        });

            // 7. Create lock file and clear session
            app(SetupService::class)->finalize();

            $this->currentStep = 7;
    }

    /**
     * Get the canonical step name for a step number.
     */
    protected function getStepName(int $step): string
    {
        return SetupService::STEPS[$step - 1] ?? 'unknown';
    }

    /**
     * Get step number from step name.
     */
    protected function getStepNumber(string $name): int
    {
        $index = array_search($name, SetupService::STEPS);

        return $index !== false ? $index + 1 : 1;
    }

    #[Layout('components.layouts.auth')]
    public function render()
    {
        return view('livewire.setup.setup-wizard', [
            'appName' => AppInfo::get('name', 'Internara'),
            'appVersion' => AppInfo::version(),
            'progress' => app(SetupService::class)->getProgress(),
        ]);
    }
}
