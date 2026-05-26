<?php

declare(strict_types=1);

namespace App\Domain\Setup\Livewire;

use App\Domain\Core\Support\SmartLogger;
use App\Domain\Settings\Support\AppInfo;
use App\Domain\Setup\Actions\FinalizeSetupAction;
use App\Domain\Setup\Livewire\Forms\AdminForm;
use App\Domain\Setup\Livewire\Forms\DepartmentForm;
use App\Domain\Setup\Livewire\Forms\InternshipForm;
use App\Domain\Setup\Livewire\Forms\SchoolForm;
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

    public SchoolForm $schoolForm;

    public DepartmentForm $departmentForm;

    public AdminForm $adminForm;

    public InternshipForm $internshipForm;

    public bool $dataVerified = false;

    public bool $securityAware = false;

    public string $recoveryKey = '';

    public function mount(): void
    {
        $state = Setup::state();

        if ($state->isInstalled()) {
            if (session()->get('setup.completed', false)) {
                $this->currentStep = 7;

                return;
            }

            $this->redirect(route('login'));

            return;
        }

        $this->initDefaults();
        $this->runAudit(app(EnvironmentAuditor::class));
        $this->restoreState();
    }

    protected function initDefaults(): void
    {
        $this->adminForm->name = config('setup.defaults.admin_name', 'Administrator');
        $this->adminForm->username = config('setup.defaults.admin_username', 'superadmin');
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'schoolForm.')
            || str_starts_with($property, 'departmentForm.')
            || str_starts_with($property, 'adminForm.')
            || str_starts_with($property, 'internshipForm.')
        ) {
            $this->saveState();
        }
    }

    protected function saveState(): void
    {
        session()->put('setup.form_data', [
            'school' => $this->schoolForm->all(),
            'department' => $this->departmentForm->all(),
            'admin' => $this->adminForm->only(['name', 'username', 'email']),
            'internship' => $this->internshipForm->all(),
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
                if (property_exists($this->adminForm, $key)) {
                    $this->adminForm->{$key} = $value;
                }
            }
        }

        if (isset($data['internship'])) {
            $this->internshipForm->fill($data['internship']);
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
            $this->schoolForm->validate();
        }

        if ($this->currentStep === 3) {
            $this->departmentForm->validate();
        }

        if ($this->currentStep === 4) {
            $this->adminForm->validate();
        }

        if ($this->currentStep === 5 && $this->internshipForm->isFilled()) {
            $this->internshipForm->validate();
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
            $internshipData = $this->internshipForm->name
                ? [
                    'name' => $this->internshipForm->name,
                    'description' => $this->internshipForm->description ?: null,
                    'start_date' => $this->internshipForm->start_date,
                    'end_date' => $this->internshipForm->end_date,
                ]
                : null;

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
                    'email' => $this->adminForm->email,
                    'password' => $this->adminForm->password,
                ],
                internshipData: $internshipData,
            );

            $this->currentStep = 7;
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
        return __('setup.wizard.page_title', ['app_name' => AppInfo::get('name', config('app.name'))]);
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
