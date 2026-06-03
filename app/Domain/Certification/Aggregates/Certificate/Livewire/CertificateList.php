<?php

declare(strict_types=1);

namespace App\Domain\Certification\Aggregates\Certificate\Livewire;

use App\Domain\Certification\Aggregates\Certificate\Actions\BatchIssueCertificateAction;
use App\Domain\Certification\Aggregates\Certificate\Actions\IssueCertificateAction;
use App\Domain\Certification\Aggregates\Certificate\Actions\RevokeCertificateAction;
use App\Domain\Certification\Aggregates\Certificate\Enums\CertificateStatus;
use App\Domain\Certification\Aggregates\Certificate\Models\Certificate;
use App\Domain\Certification\Aggregates\Certificate\Models\CertificateTemplate;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Enrollment\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class CertificateList extends BaseRecordManager
{
    public bool $showIssueModal = false;

    public string $issueRegistrationId = '';

    public string $issueTemplateId = '';

    public bool $showBatchIssueModal = false;

    public string $batchIssueTemplateId = '';

    public string $batchIssueFilter = 'active';

    public array $batchResults = [];

    public function headers(): array
    {
        return [
            ['key' => 'certificate_number', 'label' => __('certificate.number'), 'sortable' => true],
            ['key' => 'student_name', 'label' => __('certificate.student'), 'sortable' => true],
            ['key' => 'status', 'label' => __('certificate.status'), 'sortable' => true],
            ['key' => 'issued_at', 'label' => __('certificate.issued_at'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Certificate::query()
            ->select(['certificates.*', 'users.name as student_name'])
            ->join('registrations', 'certificates.registration_id', '=', 'registrations.id')
            ->join('mentees', 'registrations.mentee_id', '=', 'mentees.id')
            ->join('users', 'mentees.user_id', '=', 'users.id');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q) {
                $q->where('certificates.certificate_number', 'like', "%{$this->search}%")
                    ->orWhere('users.name', 'like', "%{$this->search}%");
            });
    }

    #[Computed]
    public function templates(): array
    {
        return CertificateTemplate::where('is_active', true)->get(['id', 'name'])->toArray();
    }

    #[Computed]
    public function activeRegistrations(): array
    {
        return Registration::query()
            ->where('status', 'active')
            ->with('mentee.user', 'internship')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => ($r->mentee?->user?->name ?? '?').' - '.($r->internship?->name ?? '?')])
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
        $this->validate([
            'issueRegistrationId' => ['required', 'exists:registrations,id'],
            'issueTemplateId' => ['required', 'exists:certificate_templates,id'],
        ]);

        $registration = Registration::findOrFail($this->issueRegistrationId);
        $template = CertificateTemplate::findOrFail($this->issueTemplateId);
        $issueAction->execute($registration, $template);
        flash()->success(__('certificate.issued'));
        $this->showIssueModal = false;
    }

    public function batchIssue(): void
    {
        $this->resetErrorBag();
        $this->batchIssueTemplateId = '';
        $this->batchIssueFilter = 'active';
        $this->batchResults = [];
        $this->showBatchIssueModal = true;
    }

    public function saveBatchIssue(BatchIssueCertificateAction $batchAction): void
    {
        $this->validate([
            'batchIssueTemplateId' => ['required', 'exists:certificate_templates,id'],
        ]);

        $template = CertificateTemplate::findOrFail($this->batchIssueTemplateId);

        $query = Registration::query()
            ->where('status', $this->batchIssueFilter)
            ->whereDoesntHave('certificates');

        $results = $batchAction->executeFiltered($query, $template);

        $this->batchResults = $results;
        flash()->success(__('certificate.batch_issued', [
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]));
    }

    public function revoke(Certificate $certificate, RevokeCertificateAction $revokeAction): void
    {
        $revokeAction->execute($certificate);
        flash()->success(__('certificate.revoked'));
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('certificate.certificate-list', [
            'statusOptions' => CertificateStatus::cases(),
        ]);
    }
}
