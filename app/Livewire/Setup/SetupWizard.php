<?php

declare(strict_types=1);

namespace App\Livewire\Setup;

use App\Actions\Setting\SetSettingAction;
use App\Actions\Setup\SetupDepartmentAction;
use App\Actions\Setup\SetupInternshipAction;
use App\Actions\Setup\SetupSchoolAction;
use App\Actions\Setup\SetupSuperAdminAction;
use App\Models\Setup;
use App\Services\Setup\EnvAuditor;
use App\Services\Setup\SetupService;
use App\Support\AppInfo;
use App\Support\Branding;
use App\Support\UserIdentifierGenerator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Unified setup wizard with pre-flight checks, token security, and lock file guard.
 *
 * S1 - Secure: Token-protected, lock file gate, no DB dependency before installation.
 * S2 - Sustain: Session-based state, clear step progression.
 */
#[Layout('components.layouts.guest')]
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

    public string $schoolWebsite = '';

    public string $principalName = '';

    // Step 3: Account (Admin)
    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminUsername = '';

    public string $adminPassword = '';

    public string $adminPassword_confirmation = '';

    // Step 4: Department
    public string $departmentName = '';

    public string $departmentDescription = '';

    // Step 5: Internship
    public string $internshipName = '';

    public string $internshipDescription = '';

    public string $startDate = '';

    public string $endDate = '';

    // Step 6: Finalize
    public bool $dataVerified = false;

    public bool $securityAware = false;

    public function mount(EnvAuditor $auditor): void
    {
        // S1: Double-check lock file even if middleware was bypassed
        $setup = app(SetupService::class);

        if ($setup->isInstalled()) {
            $this->redirectRoute('login');
        }

        // Restore state from session
        $this->restoreState();

        // Restore step from session (this might override the one from restoreState)
        $this->currentStep = $setup->getCurrentStep();

        // Run pre-flight audit on first visit
        $this->auditResults = $auditor->audit();
        $this->auditPassed = $this->auditResults['passed'];
    }

    /**
     * Save current form state to session.
     */
    public function updated($propertyName): void
    {
        $this->saveState();
    }

    protected function saveState(): void
    {
        session()->put('setup.form_data', [
            'schoolName' => $this->schoolName,
            'schoolCode' => $this->schoolCode,
            'schoolAddress' => $this->schoolAddress,
            'schoolEmail' => $this->schoolEmail,
            'schoolPhone' => $this->schoolPhone,
            'schoolWebsite' => $this->schoolWebsite,
            'principalName' => $this->principalName,
            'adminName' => $this->adminName,
            'adminEmail' => $this->adminEmail,
            'adminUsername' => $this->adminUsername,
            'departmentName' => $this->departmentName,
            'departmentDescription' => $this->departmentDescription,
            'internshipName' => $this->internshipName,
            'internshipDescription' => $this->internshipDescription,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'dataVerified' => $this->dataVerified,
            'securityAware' => $this->securityAware,
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

        // Generate username if empty
        if (empty($this->adminUsername)) {
            $this->adminUsername = UserIdentifierGenerator::generateUsername();
            $this->saveState();
        }
    }

    public function runAudit(EnvAuditor $auditor): void
    {
        $this->auditResults = $auditor->audit();
        $this->auditPassed = $this->auditResults['passed'];

        if (! $this->auditPassed) {
            flash()->error(__('setup.wizard.requirements_not_met'));
        }
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1 && ! $this->auditPassed) {
            flash()->warning(__('setup.wizard.audit_must_pass'));

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
                'schoolWebsite' => 'nullable|url|max:255',
            ],
            3 => [
                'adminName' => 'required|string|max:255',
                'adminEmail' => 'required|email|unique:users,email',
                'adminPassword' => 'required|string|min:8|confirmed',
            ],
            4 => [
                'departmentName' => 'required|string|max:255',
                'departmentDescription' => 'nullable|string|max:1000',
            ],
            5 => [
                'internshipName' => 'required|string|max:255',
                'internshipDescription' => 'nullable|string|max:1000',
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
                'website' => $this->schoolWebsite ?: null,
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
                'description' => $this->departmentDescription,
                'school_id' => $school->id,
            ]);

            // 4. Setup Internship (step 5)
            $internship = $setupInternship->execute([
                'name' => $this->internshipName,
                'description' => $this->internshipDescription,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'status' => 'active',
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
            app(SetSettingAction::class)->execute('setup_completed', 'true', 'boolean', 'system');
        });

        // 7. Create lock file and clear session
        app(SetupService::class)->finalize();

        $this->currentStep = 7;

        flash()->success(__('setup.wizard.setup_complete'));
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

    /**
     * Navigate directly to a specific step by name.
     */
    public function goToStep(string $stepName): void
    {
        $stepNumber = $this->getStepNumber($stepName);

        // Only allow navigating back or to steps already completed
        if ($stepNumber < $this->currentStep || app(SetupService::class)->isStepCompleted($stepName)) {
            $this->currentStep = $stepNumber;
            app(SetupService::class)->setCurrentStep($this->currentStep);
        }
    }

    public function render()
    {
        return view('livewire.setup.setup-wizard', [
            'appName' => Branding::appName(),
            'appVersion' => AppInfo::version(),
            'progress' => app(SetupService::class)->getProgress(),
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return __('setup.wizard.attributes');
    }
}
