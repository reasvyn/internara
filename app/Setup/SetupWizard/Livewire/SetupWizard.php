<?php

declare(strict_types=1);

namespace App\Setup\SetupWizard\Livewire;

use App\Core\Support\AppInfo;
use App\Core\Support\SmartLogger;
use App\Setup\Entities\SetupEntity;
use App\Setup\SetupWizard\Actions\FinalizeSetupAction;
use App\Setup\SetupWizard\Livewire\Forms\DepartmentForm;
use App\Setup\SetupWizard\Livewire\Forms\SchoolForm;
use App\Setup\SetupWizard\Livewire\Forms\SuperAdminForm;
use App\SysAdmin\Observability\Services\EnvironmentAuditor;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('setup.layouts.setup')]
class SetupWizard extends Component
{
    private const array STEP_KEYS = [
        'welcome',
        'account',
        'school',
        'department',
        'finalize',
        'complete',
    ];

    public int $currentStep = 1;

    public array $audit = [];

    public bool $auditPassed = false;

    public SchoolForm $schoolForm;

    public DepartmentForm $departmentForm;

    public SuperAdminForm $superAdminForm;

    public bool $showGuide = false;

    public bool $dataVerified = false;

    public bool $securityAware = false;

    public string $recoveryKey = '';

    public function mount(): void
    {
        try {
            $state = SetupEntity::get();
        } catch (\Throwable $e) {
            SmartLogger::error('Setup wizard mount failed')
                ->module('Setup')
                ->event('wizard.mount_failed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
            $this->redirect(route('login'));

            return;
        }

        if ($state->isInstalled()) {
            if (session()->get('setup.completed', false)) {
                $this->currentStep = 6;

                return;
            }

            $this->redirect(route('login'));

            return;
        }

        $this->initDefaults();

        try {
            $this->runAudit(app(EnvironmentAuditor::class));
        } catch (\Throwable $e) {
            SmartLogger::error('Setup wizard audit failed during mount')
                ->module('Setup')
                ->event('wizard.audit_failed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
            $this->audit = ['categories' => []];
            $this->auditPassed = false;
        }

        $this->restoreState();
    }

    protected function initDefaults(): void
    {
        $this->superAdminForm->name = config('setup.defaults.admin_name', 'Administrator');
        $this->superAdminForm->username = config('setup.defaults.admin_username', 'superadmin');
    }

    public function updated(string $property): void
    {
        if (
            str_starts_with($property, 'schoolForm.') ||
            str_starts_with($property, 'departmentForm.') ||
            str_starts_with($property, 'superAdminForm.')
        ) {
            $this->saveState();
        }
    }

    protected function saveState(): void
    {
        session()->put('setup.form_data', [
            'school' => $this->schoolForm->all(),
            'department' => $this->departmentForm->all(),
            'admin' => $this->superAdminForm->only(['name', 'username', 'email']),
        ]);
    }

    protected function restoreState(): void
    {
        $data = session()->get('setup.form_data', []);

        if (isset($data['school'])) {
            $this->schoolForm->fill($data['school']);
        }

        if (isset($data['department'])) {
            $this->departmentForm->fill($data['department']);
        }

        if (isset($data['admin'])) {
            foreach ($data['admin'] as $key => $value) {
                if (property_exists($this->superAdminForm, $key)) {
                    $this->superAdminForm->{$key} = $value;
                }
            }
        }

    }

    public function runAudit(EnvironmentAuditor $auditor): void
    {
        try {
            $report = $auditor->audit();
        } catch (\Throwable $e) {
            SmartLogger::error('Environment auditor threw exception')
                ->module('Setup')
                ->event('wizard.audit_exception')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
            $this->audit = ['categories' => []];
            $this->auditPassed = false;

            return;
        }

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

        $this->validateCurrentStep();

        $this->currentStep++;
    }

    private function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            2 => $this->superAdminForm->validate(),
            3 => $this->schoolForm->validate(),
            4 => $this->departmentForm->validate(),
            default => null,
        };
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

        if ($targetStep < $this->currentStep || SetupEntity::get()->isStepCompleted($stepKey)) {
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
            $this->recoveryKey = $finalizeSetup->execute(
                schoolData: [
                    'name' => $this->schoolForm->name,
                    'institutional_code' => $this->schoolForm->institutional_code,
                    'address' => $this->schoolForm->address,
                    'email' => $this->schoolForm->email,
                    'phone' => $this->schoolForm->phone,
                    'website' => $this->schoolForm->website,
                    'principal_name' => $this->schoolForm->principal_name,
                ],
                departmentData: [
                    'name' => $this->departmentForm->name,
                    'description' => $this->departmentForm->description,
                ],
                adminData: [
                    'email' => $this->superAdminForm->email,
                    'password' => $this->superAdminForm->password,
                ],
            );

            $this->currentStep = 6;
            session()->put('setup.completed', true);
            flash()->success(__('setup.wizard.setup_complete'));
        } catch (\RuntimeException $e) {
            SmartLogger::error('Setup wizard failed')
                ->module('Setup')
                ->event('wizard.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
            flash()->error(__('setup.wizard.install_failed_generic'));
        } catch (\Throwable $e) {
            SmartLogger::error('Setup wizard crashed')
                ->module('Setup')
                ->event('wizard.crashed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
            flash()->error(__('setup.wizard.install_failed_generic'));
        }
    }

    public function finishSession(): void
    {
        session()->forget('setup.completed');
        $this->redirect(route('login'));
    }

    public function title(): string
    {
        return __('setup.wizard.page_title', [
            'app_name' => AppInfo::get('name', config('app.name')),
        ]);
    }

    public function render(): View
    {
        return view('setup.setup-wizard.setup-wizard', [
            'appName' => AppInfo::get('name', config('app.name')),
            'appVersion' => AppInfo::version(),
            'stepKeys' => self::STEP_KEYS,
        ]);
    }
}
