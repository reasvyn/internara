<?php

declare(strict_types=1);

namespace App\Domain\Setup\Livewire;

use App\Domain\Core\Support\SmartLogger;
use App\Domain\Settings\Support\AppInfo;
use App\Domain\Setup\Actions\FinalizeSetupAction;
use App\Domain\Setup\Models\Setup;
use App\Domain\Setup\Services\EnvironmentAuditor;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('setup::layouts.setup')]
class SetupWizard extends Component
{
    public int $currentStep = 1;

    public array $audit = [];

    public bool $auditPassed = false;

    public array $schoolData = [
        'name' => '',
        'institutional_code' => '',
        'address' => '',
        'email' => '',
        'phone' => '',
        'website' => '',
        'principal_name' => '',
    ];

    public array $departmentData = [
        'name' => '',
        'description' => '',
    ];

    public array $adminData = [
        'name' => '',
        'username' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public array $internshipData = [
        'name' => '',
        'description' => '',
        'start_date' => '',
        'end_date' => '',
    ];

    public bool $dataVerified = false;

    public bool $securityAware = false;

    public string $recoveryKey = '';

    public function mount(): void
    {
        if (Setup::state()->isInstalled()) {
            $this->redirect(route('login'));

            return;
        }

        $this->initDefaults();
        $this->runAudit(app(EnvironmentAuditor::class));
        $this->restoreState();
    }

    protected function initDefaults(): void
    {
        $this->adminData['name'] = config('setup.defaults.admin_name', 'Administrator');
        $this->adminData['username'] = config('setup.defaults.admin_username', 'administrator');
    }

    public function updated(string $property): void
    {
        $this->saveState();
    }

    protected function saveState(): void
    {
        session()->put('setup.form_data', [
            'schoolData' => $this->schoolData,
            'departmentData' => $this->departmentData,
            'adminData' => [
                'name' => $this->adminData['name'],
                'username' => $this->adminData['username'],
                'email' => $this->adminData['email'],
            ],
            'internshipData' => $this->internshipData,
        ]);
    }

    protected function restoreState(): void
    {
        $data = session()->get('setup.form_data', []);

        if (isset($data['schoolData'])) {
            $this->schoolData = array_merge($this->schoolData, $data['schoolData']);
        }

        if (isset($data['departmentData'])) {
            $this->departmentData = array_merge($this->departmentData, $data['departmentData']);
        }

        if (isset($data['adminData'])) {
            foreach ($data['adminData'] as $key => $value) {
                if (array_key_exists($key, $this->adminData)) {
                    $this->adminData[$key] = $value;
                }
            }
        }

        if (isset($data['internshipData'])) {
            $this->internshipData = array_merge($this->internshipData, $data['internshipData']);
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
                'schoolData.name' => 'required|string|max:255',
                'schoolData.institutional_code' => 'required|string|max:50',
                'schoolData.email' => 'required|email|max:255',
            ]);
        }

        if ($this->currentStep === 3) {
            $this->validate([
                'departmentData.name' => 'required|string|max:255',
            ]);
        }

        if ($this->currentStep === 4) {
            $this->validate([
                'adminData.email' => 'required|email|max:255',
                'adminData.password' => 'required|string|min:8|confirmed',
            ]);
        }

        if ($this->currentStep === 5) {
            $this->validate([
                'internshipData.name' => 'required|string|max:255',
                'internshipData.start_date' => 'required|date',
                'internshipData.end_date' => 'required|date|after:internshipData.start_date',
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
        $stepKeys = config('setup.wizard.step_keys', ['welcome', 'school', 'department', 'account', 'internship', 'finalize', 'complete']);
        $stepIndex = array_search($stepKey, $stepKeys, true);

        if ($stepIndex === false) {
            return;
        }

        $targetStep = $stepIndex + 1;

        if ($targetStep < $this->currentStep || Setup::state()->isStepCompleted($stepKey)) {
            $this->currentStep = $targetStep;
        }
    }

    public function finish(FinalizeSetupAction $finalizeSetup): void
    {
        $this->validate([
            'dataVerified' => 'accepted',
            'securityAware' => 'accepted',
        ]);

        try {
            $internshipData = $this->internshipData['name']
                ? [
                    'name' => $this->internshipData['name'],
                    'description' => $this->internshipData['description'] ?: null,
                    'start_date' => $this->internshipData['start_date'],
                    'end_date' => $this->internshipData['end_date'],
                ]
                : null;

            $this->recoveryKey = $finalizeSetup->execute(
                schoolData: $this->schoolData,
                departmentData: $this->departmentData,
                adminData: $this->adminData,
                internshipData: $internshipData,
            );

            $this->currentStep = 7;
            flash()->success(__('setup.wizard.setup_complete'));
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
            SmartLogger::error('Setup wizard failed')
                ->module('Setup')
                ->event('wizard.failed')
                ->withPayload(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()])
                ->systemOnly()
                ->save();
        } catch (\Throwable $e) {
            SmartLogger::error('Setup wizard crashed')
                ->module('Setup')
                ->event('wizard.crashed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
            flash()->error(__('setup.wizard.install_failed', ['message' => $e->getMessage()]));
        }
    }

    public function finishSession(): void
    {
        $this->redirect(route('login'));
    }

    public function render(): View
    {
        $stepKeys = config('setup.wizard.step_keys', ['welcome', 'school', 'department', 'account', 'internship', 'finalize', 'complete']);

        return view('setup.setup-wizard', [
            'appName' => AppInfo::get('name', config('app.name')),
            'appVersion' => AppInfo::version(),
            'stepKeys' => $stepKeys,
        ]);
    }
}
