<?php

declare(strict_types=1);

namespace App\Modules\Setup\Domain\SetupWizard\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Livewire\BaseWizard;
use App\Modules\Core\Services\AppInfo;
use App\Modules\Core\Services\SmartLogger;
use App\Modules\Setup\Domain\SetupWizard\Actions\FinalizeSetupAction;
use App\Modules\Setup\Domain\SetupWizard\Data\FinalizeSetupData;
use App\Modules\Setup\Domain\SetupWizard\Livewire\Forms\DepartmentForm;
use App\Modules\Setup\Domain\SetupWizard\Livewire\Forms\SchoolForm;
use App\Modules\Setup\Domain\SetupWizard\Livewire\Forms\SuperAdminForm;
use App\Modules\Setup\Entities\SetupEntity;
use App\Modules\SysAdmin\Domain\Observability\Services\EnvironmentAuditor;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Features\SupportRedirects\Redirector;
use TallStackUi\Traits\Interactions;

#[Layout('setup.layouts.setup')]
class SetupWizard extends BaseWizard
{
    use Interactions;

    protected function steps(): array
    {
        return ['welcome', 'account', 'school', 'department', 'finalize', 'complete'];
    }

    public array $audit = [];

    public bool $auditPassed = false;

    public SchoolForm $schoolForm;

    public DepartmentForm $departmentForm;

    public SuperAdminForm $superAdminForm;

    public bool $showGuide = false;

    public bool $dataVerified = false;

    public bool $securityAware = false;

    public string $recoveryKey = '';

    public function mount(EnvironmentAuditor $auditor): void
    {
        try {
            $state = SetupEntity::get();
        } catch (\Throwable $e) {
            SmartLogger::error('Setup wizard mount failed')
                ->module('Setup')
                ->event('wizard.mount_failed')
                ->withPayload(['error' => $e->getMessage()])
                ->withPiiMasking()
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
            $this->runAudit($auditor);
        } catch (\Throwable $e) {
            SmartLogger::error('Setup wizard audit failed during mount')
                ->module('Setup')
                ->event('wizard.audit_failed')
                ->withPayload(['error' => $e->getMessage()])
                ->withPiiMasking()
                ->systemOnly()
                ->save();
            $this->audit = ['categories' => []];
            $this->auditPassed = false;
        }

        $this->restoreState();
    }

    protected function initDefaults(): void
    {
        $this->superAdminForm->name = config('setup.defaults.admin_name', 'Super Admin');
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
                ->withPiiMasking()
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

        foreach ($categories as &$category) {
            $statuses = array_column($category['checks'], 'status');
            $category['has_issue'] = in_array('fail', $statuses) || in_array('warn', $statuses);
            $category['icon'] = match (true) {
                in_array('fail', $statuses) => 'fail',
                in_array('warn', $statuses) => 'warn',
                default => 'pass',
            };
        }
        unset($category);

        $this->audit = ['categories' => $categories];
        $this->auditPassed = $report->passed();
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1 && ! $this->auditPassed) {
            $this->toast()->error(__('setup.wizard.audit_must_pass'))->send();

            return;
        }

        parent::nextStep();
    }

    protected function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            2 => $this->superAdminForm->validate(),
            3 => $this->schoolForm->validate(),
            4 => $this->departmentForm->validate(),
            default => null,
        };
    }

    public function goToStepByKey(string $stepKey): void
    {
        $stepIndex = array_search($stepKey, $this->steps(), true);

        if ($stepIndex === false) {
            return;
        }

        $targetStep = $stepIndex + 1;

        if ($targetStep < $this->currentStep || SetupEntity::get()->isStepCompleted($stepKey)) {
            $this->goToStep($targetStep);
        }
    }

    public function finish(FinalizeSetupAction $finalizeSetup): void
    {
        $this->validate([
            'dataVerified' => 'accepted',
            'securityAware' => 'accepted',
        ]);

        try {
            $this->recoveryKey = $finalizeSetup->execute(new FinalizeSetupData(
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
            ));

            $this->currentStep = 6;
            session()->put('setup.completed', true);
            $this->toast()->success(__('setup.wizard.setup_complete'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        } catch (\Throwable $e) {
            SmartLogger::error('Setup wizard crashed')
                ->module('Setup')
                ->event('wizard.crashed')
                ->withPayload(['error' => $e->getMessage()])
                ->withPiiMasking()
                ->systemOnly()
                ->save();
            $this->toast()->error(__('setup.wizard.install_failed_generic'))->send();
        }
    }

    public function finishSession(): Redirector
    {
        session()->forget('setup.completed');

        return redirect()->to(route('login'));
    }

    public function title(): string
    {
        $stepKey = $this->currentStepKey();
        $stepLabel = $stepKey ? __("setup.wizard.step_labels.{$stepKey}") : '';

        return __('setup.wizard.page_title', [
            'step' => $stepLabel,
            'app_name' => AppInfo::get('name', config('app.name')),
        ]);
    }

    public function render(): View
    {
        return view('setup.setup-wizard.setup-wizard', [
            'appName' => AppInfo::get('name', config('app.name')),
            'appVersion' => AppInfo::version(),
            'stepKeys' => $this->steps(),
        ]);
    }
}
