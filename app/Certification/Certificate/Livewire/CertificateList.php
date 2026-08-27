<?php

declare(strict_types=1);

namespace App\Certification\Certificate\Livewire;

use App\Certification\Actions\DispatchBatchIssueCertificatesAction;
use App\Certification\Certificate\Actions\IssueCertificateAction;
use App\Certification\Certificate\Actions\RevokeCertificateAction;
use App\Certification\Certificate\Enums\CertificateStatus;
use App\Certification\Certificate\Models\Certificate;
use App\Certification\Certificate\Models\CertificateTemplate;
use App\Certification\Data\BatchIssueCertificatesData;
use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Enrollment\Registration\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class CertificateList extends BaseRecordManager
{
    use Interactions;

    public bool $showIssueModal = false;

    public ?string $confirmTarget = null;

    public string $issueRegistrationId = '';

    public string $issueTemplateId = '';

    public bool $showBatchIssueModal = false;

    public string $batchIssueTemplateId = '';

    public string $batchIssueFilter = 'active';

    public function headers(): array
    {
        return [
            [
                'index' => 'certificate_number',
                'label' => __('certificate.number'),
                'sortable' => true,
            ],
            ['index' => 'student_name', 'label' => __('certificate.student'), 'sortable' => true],
            ['index' => 'status', 'label' => __('certificate.filter_status'), 'sortable' => true],
            ['index' => 'issued_at', 'label' => __('certificate.issued_at'), 'sortable' => true],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Certificate::query()
            ->select(['certificates.*', 'users.name as student_name'])
            ->join('registrations', 'certificates.registration_id', '=', 'registrations.id')
            ->join('users', 'registrations.student_id', '=', 'users.id');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('certificates.certificate_number', 'like', "%{$this->search}%")->orWhere(
                'users.name',
                'like',
                "%{$this->search}%",
            );
        });
    }

    #[Computed]
    public function templates(): array
    {
        return CertificateTemplate::where('is_active', true)
            ->get(['id', 'name'])
            ->toArray();
    }

    #[Computed]
    public function activeRegistrations(): array
    {
        return Registration::query()
            ->where('status', 'active')
            ->with('student', 'internship')
            ->get()
            ->map(
                fn ($r) => [
                    'id' => $r->id,
                    'name' => ($r->student?->name ?? '?').' - '.($r->internship?->name ?? '?'),
                ],
            )
            ->toArray();
    }

    public function issue(): void
    {
        $this->resetErrorBag();
        $this->issueRegistrationId = '';
        $this->issueTemplateId = '';
        $this->showIssueModal = true;
    }

    public function saveIssue(IssueCertificateAction $issueAction): void
    {
        $this->authorize('create', Certificate::class);

        $this->validate([
            'issueRegistrationId' => ['required', 'exists:registrations,id'],
            'issueTemplateId' => ['required', 'exists:certificate_templates,id'],
        ]);

        $registration = Registration::findOrFail($this->issueRegistrationId);
        $template = CertificateTemplate::findOrFail($this->issueTemplateId);
        $issueAction->execute($registration, $template);
        $this->toast()->success(__('certificate.issued'))->send();
        $this->showIssueModal = false;
    }

    public function batchIssue(): void
    {
        $this->resetErrorBag();
        $this->batchIssueTemplateId = '';
        $this->batchIssueFilter = 'active';
        $this->showBatchIssueModal = true;
    }

    public function saveBatchIssue(DispatchBatchIssueCertificatesAction $dispatchAction): void
    {
        $this->authorize('create', Certificate::class);

        $this->validate([
            'batchIssueTemplateId' => ['required', 'exists:certificate_templates,id'],
        ]);

        $template = CertificateTemplate::findOrFail($this->batchIssueTemplateId);

        $registrationIds = Registration::query()
            ->where('status', $this->batchIssueFilter)
            ->whereDoesntHave('certificates')
            ->pluck('id')
            ->all();

        if ($registrationIds === []) {
            $this->toast()->warning(__('certificate.batch_empty'))->send();
            $this->showBatchIssueModal = false;

            return;
        }

        $dispatchAction->execute(new BatchIssueCertificatesData(
            registrationIds: $registrationIds,
            status: $this->batchIssueFilter,
            templateId: $template->id,
        ));

        $this->showBatchIssueModal = false;
        $this->toast()->success(__('certificate.batch_queued'))->send();
    }

    public function askRevoke(string $id): void
    {
        $this->confirmTarget = $id;
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmAction(RevokeCertificateAction $revokeAction): void
    {
        try {
            $certificate = Certificate::findOrFail($this->confirmTarget);
            $this->authorize('revoke', $certificate);
            $revokeAction->execute($certificate);
            $this->toast()->success(__('certificate.revoked'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
        $this->confirmTarget = null;
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('certification.certificate.certificate-list', [
            'statusOptions' => CertificateStatus::cases(),
        ]);
    }
}
