<?php

declare(strict_types=1);

namespace App\Livewire\Setup;

use App\Actions\Setup\InstallSystemAction;
use App\Actions\Setup\SetupDepartmentAction;
use App\Actions\Setup\SetupInternshipAction;
use App\Actions\Setup\SetupSchoolAction;
use App\Actions\Setup\SetupSuperAdminAction;
use App\Support\AppInfo;
use App\Support\Settings;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SetupWizard extends Component
{
    public int $currentStep = 1;

    // Step 2: School
    public string $schoolName = '';
    public string $schoolCode = '';
    public string $schoolAddress = '';
    public string $principalName = '';

    // Step 3: Department
    public string $departmentName = '';

    // Step 4: Internship
    public string $internshipName = '';
    public string $startDate = '';
    public string $endDate = '';

    // Step 5: Account
    public string $adminName = '';
    public string $adminEmail = '';
    public string $adminUsername = '';
    public string $adminPassword = '';
    public string $adminPasswordConfirmation = '';

    public function mount(): void
    {
        // Security: Only allow setup if not already completed
        if (Settings::get('setup_completed')) {
            $this->redirectRoute('login');
        }
    }

    public function nextStep(): void
    {
        $this->validateStep();
        $this->currentStep++;
    }

    public function prevStep(): void
    {
        $this->currentStep--;
    }

    protected function validateStep(): void
    {
        $rules = match($this->currentStep) {
            2 => [
                'schoolName' => 'required|string|max:255',
                'schoolCode' => 'required|string|unique:schools,institutional_code',
                'schoolAddress' => 'required|string',
            ],
            3 => [
                'departmentName' => 'required|string|max:255',
            ],
            4 => [
                'internshipName' => 'required|string|max:255',
                'startDate' => 'required|date',
                'endDate' => 'required|date|after:startDate',
            ],
            5 => [
                'adminName' => 'required|string|max:255',
                'adminEmail' => 'required|email|unique:users,email',
                'adminUsername' => 'required|string|min:4|unique:users,username',
                'adminPassword' => 'required|string|min:8|confirmed',
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
        $this->validateStep();

        // 1. Setup School
        $school = $setupSchool->execute([
            'name' => $this->schoolName,
            'institutional_code' => $this->schoolCode,
            'address' => $this->schoolAddress,
            'principal_name' => $this->principalName,
        ]);

        // 2. Setup Department
        $setupDepartment->execute([
            'name' => $this->departmentName,
            'school_id' => $school->id,
        ]);

        // 3. Setup Internship
        $setupInternship->execute([
            'name' => $this->internshipName,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'status' => 'active',
        ]);

        // 4. Setup Super Admin
        $setupSuperAdmin->execute([
            'name' => $this->adminName,
            'email' => $this->adminEmail,
            'username' => $this->adminUsername,
            'password' => $this->adminPassword,
        ]);

        // 5. Mark Setup as Completed
        \App\Models\Setting::updateOrCreate(['key' => 'setup_completed'], ['value' => 'true']);

        session()->flash('success', 'System setup completed successfully!');
        $this->currentStep = 6;
    }

    #[Layout('components.layouts.app')] // We might need a blank layout for setup
    public function render()
    {
        return view('livewire.setup.setup-wizard', [
            'appName' => AppInfo::get('name', 'Internara'),
            'appVersion' => AppInfo::version(),
        ]);
    }
}
