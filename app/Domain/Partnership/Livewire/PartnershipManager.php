<?php

declare(strict_types=1);

namespace App\Domain\Partnership\Livewire;

use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Partnership\Actions\CreatePartnershipAction;
use App\Domain\Partnership\Actions\DeletePartnershipAction;
use App\Domain\Partnership\Actions\TerminatePartnershipAction;
use App\Domain\Partnership\Actions\UpdatePartnershipAction;
use App\Domain\Partnership\Enums\PartnershipStatus;
use App\Domain\Partnership\Models\Company;
use App\Domain\Partnership\Models\Partnership;
use App\Domain\Shared\Support\CsvHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

class PartnershipManager extends BaseRecordManager
{
    use WithFileUploads;

    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public $importFile = null;

    public $mouDocument = null;

    public array $formData = [
        'id' => null,
        'company_id' => '',
        'agreement_number' => '',
        'title' => '',
        'start_date' => '',
        'end_date' => '',
        'scope' => '',
        'contact_person_name' => '',
        'contact_person_phone' => '',
        'contact_person_email' => '',
        'signed_by_school' => '',
        'signed_by_company' => '',
        'signed_at' => '',
        'notes' => '',
    ];

    public function headers(): array
    {
        return [
            ['key' => 'agreement_number', 'label' => __('partnership.agreement_number'), 'sortable' => true],
            ['key' => 'company_name', 'label' => __('partnership.company'), 'sortable' => true],
            ['key' => 'title', 'label' => __('partnership.title_field'), 'sortable' => true],
            ['key' => 'start_date', 'label' => __('partnership.start_date'), 'sortable' => true],
            ['key' => 'end_date', 'label' => __('partnership.end_date'), 'sortable' => true],
            ['key' => 'status', 'label' => __('partnership.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Partnership::query()
            ->select(['partnerships.*', 'companies.name as company_name'])
            ->join('companies', 'partnerships.company_id', '=', 'companies.id');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q) {
                $q->where('partnerships.agreement_number', 'like', "%{$this->search}%")
                    ->orWhere('partnerships.title', 'like', "%{$this->search}%")
                    ->orWhere('companies.name', 'like', "%{$this->search}%");
            });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('partnerships.status', $v))
            ->when($this->filters['company_id'] ?? null, fn ($q, $v) => $q->where('partnerships.company_id', $v));
    }

    #[Computed]
    public function stats(): array
    {
        $threshold = 30;

        $expiringSoon = Partnership::query()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now())
            ->whereDate('end_date', '<=', now()->addDays($threshold))
            ->count();

        $active = Partnership::where('status', 'active')->count();
        $expired = Partnership::where('status', 'expired')->count();

        return [
            'active' => $active,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'total' => Partnership::count(),
        ];
    }

    #[Computed]
    public function companies(): array
    {
        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    #[Computed]
    public function statusOptions(): array
    {
        return collect(PartnershipStatus::cases())
            ->map(fn ($s) => ['id' => $s->value, 'name' => $s->label()])
            ->toArray();
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => null,
            'company_id' => '',
            'agreement_number' => '',
            'title' => '',
            'start_date' => '',
            'end_date' => '',
            'scope' => '',
            'contact_person_name' => '',
            'contact_person_phone' => '',
            'contact_person_email' => '',
            'signed_by_school' => '',
            'signed_by_company' => '',
            'signed_at' => '',
            'notes' => '',
        ];
        $this->mouDocument = null;
        $this->showModal = true;
    }

    public function edit(Partnership $partnership): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => $partnership->id,
            'company_id' => $partnership->company_id,
            'agreement_number' => $partnership->agreement_number,
            'title' => $partnership->title,
            'start_date' => $partnership->start_date?->format('Y-m-d') ?? '',
            'end_date' => $partnership->end_date?->format('Y-m-d') ?? '',
            'scope' => $partnership->scope ?? '',
            'contact_person_name' => $partnership->contact_person_name ?? '',
            'contact_person_phone' => $partnership->contact_person_phone ?? '',
            'contact_person_email' => $partnership->contact_person_email ?? '',
            'signed_by_school' => $partnership->signed_by_school ?? '',
            'signed_by_company' => $partnership->signed_by_company ?? '',
            'signed_at' => $partnership->signed_at?->format('Y-m-d') ?? '',
            'notes' => $partnership->notes ?? '',
        ];
        $this->showModal = true;
    }

    public function save(CreatePartnershipAction $create, UpdatePartnershipAction $update): void
    {
        $this->validate([
            'formData.company_id' => ['required', 'exists:companies,id'],
            'formData.agreement_number' => ['required', 'string', 'max:100', 'unique:partnerships,agreement_number,'.($this->formData['id'] ?? 'NULL')],
            'formData.title' => ['required', 'string', 'max:255'],
            'formData.start_date' => ['required', 'date'],
            'formData.end_date' => ['required', 'date', 'after_or_equal:formData.start_date'],
            'formData.scope' => ['nullable', 'string', 'max:5000'],
            'formData.contact_person_name' => ['nullable', 'string', 'max:255'],
            'formData.contact_person_phone' => ['nullable', 'string', 'max:30'],
            'formData.contact_person_email' => ['nullable', 'email', 'max:255'],
            'formData.signed_by_school' => ['nullable', 'string', 'max:255'],
            'formData.signed_by_company' => ['nullable', 'string', 'max:255'],
            'formData.signed_at' => ['nullable', 'date'],
            'formData.notes' => ['nullable', 'string'],
            'mouDocument' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($this->formData['id']) {
            $partnership = Partnership::findOrFail($this->formData['id']);
            $update->execute($partnership, $this->formData);
            $this->uploadMouDocument($partnership);
            flash()->success(__('partnership.update_success'));
        } else {
            $partnership = $create->execute($this->formData);
            $this->uploadMouDocument($partnership);
            flash()->success(__('partnership.save_success'));
        }

        $this->showModal = false;
    }

    // --- Direct Actions (used by tests) ---

    public function terminate(Partnership $partnership, TerminatePartnershipAction $terminateAction): void
    {
        $terminateAction->execute($partnership);
        flash()->success(__('partnership.terminate_success'));
    }

    // --- Confirm Dialog ---

    public function askDelete(string $id): void
    {
        $partnership = Partnership::findOrFail($id);
        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->confirmMessage = __('partnership.delete_confirm');
        $this->showConfirm = true;
    }

    public function askTerminate(string $id): void
    {
        $partnership = Partnership::findOrFail($id);
        $this->confirmTarget = $id;
        $this->confirmType = 'terminate';
        $this->confirmMessage = __('partnership.terminate_confirm');
        $this->showConfirm = true;
    }

    public function askDeleteSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $this->confirmType = 'delete_selected';
        $this->confirmMessage = __('partnership.delete_selected_confirm');
        $this->showConfirm = true;
    }

    public function confirmAction(
        DeletePartnershipAction $deleteAction,
        TerminatePartnershipAction $terminateAction,
    ): void {
        if ($this->confirmTarget === null && $this->confirmType !== 'delete_selected') {
            return;
        }

        try {
            match ($this->confirmType) {
                'delete' => $this->executeDelete($this->confirmTarget, $deleteAction),
                'terminate' => $this->executeTerminate($this->confirmTarget, $terminateAction),
                'delete_selected' => $this->executeDeleteSelected($deleteAction),
                default => null,
            };
        } catch (RejectedException|\RuntimeException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    private function executeDelete(string $id, DeletePartnershipAction $action): void
    {
        $partnership = Partnership::findOrFail($id);

        if (! $partnership->asPartnershipState()->canBeDeleted()) {
            flash()->error(__('partnership.delete_blocked'));

            return;
        }

        $action->execute($partnership);
        flash()->success(__('partnership.delete_success'));
    }

    private function executeTerminate(string $id, TerminatePartnershipAction $action): void
    {
        $partnership = Partnership::findOrFail($id);
        $action->execute($partnership);
        flash()->success(__('partnership.terminate_success'));
    }

    private function executeDeleteSelected(DeletePartnershipAction $action): void
    {
        $count = 0;

        foreach ($this->selectedIds as $id) {
            $partnership = Partnership::find($id);

            if ($partnership && $partnership->asPartnershipState()->canBeDeleted()) {
                $action->execute($partnership);
                $count++;
            }
        }

        if ($count > 0) {
            flash()->success(__('common.actions.bulk_action_done', ['count' => $count, 'action' => __('common.actions.delete')]));
        }

        $this->clearSelection();
    }

    // --- Import / Export ---

    public function import(CsvHandler $csv): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $csv->import($this->importFile->getRealPath(), function (array $row) {
            $agreementNumber = trim($row[0] ?? '');

            if ($agreementNumber === '') {
                return null;
            }

            if (Partnership::where('agreement_number', $agreementNumber)->exists()) {
                return 'skipped';
            }

            Partnership::create([
                'agreement_number' => $agreementNumber,
                'title' => trim($row[1] ?? ''),
                'start_date' => trim($row[2] ?? '') ?: now(),
                'end_date' => trim($row[3] ?? '') ?: now()->addYear(),
                'scope' => trim($row[4] ?? '') ?: null,
            ]);

            return 'created';
        });

        $this->importFile = null;

        if ($result['invalid']) {
            flash()->error(__('common.actions.import_invalid'));

            return;
        }

        flash()->success(__('common.actions.import_summary', [
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ]));
    }

    public function export(CsvHandler $csv): mixed
    {
        $partnerships = Partnership::with('company')
            ->when($this->search, fn ($q) => $q->where('agreement_number', 'like', "%{$this->search}%"))
            ->orderBy('agreement_number')
            ->get();

        return $csv->export(
            $partnerships,
            [__('partnership.agreement_number'), __('partnership.title_field'), __('partnership.company'), __('partnership.start_date'), __('partnership.end_date')],
            fn ($p) => [$p->agreement_number, $p->title, $p->company?->name ?? '', $p->start_date?->format('Y-m-d') ?? '', $p->end_date?->format('Y-m-d') ?? ''],
        )->send();
    }

    public function exportSelected(CsvHandler $csv): mixed
    {
        if ($this->selectedIds === []) {
            flash()->warning(__('common.actions.no_records_selected'));

            return null;
        }

        $partnerships = Partnership::with('company')
            ->whereIn('id', $this->selectedIds)
            ->orderBy('agreement_number')
            ->get();

        return $csv->export(
            $partnerships,
            [__('partnership.agreement_number'), __('partnership.title_field'), __('partnership.company'), __('partnership.start_date'), __('partnership.end_date')],
            fn ($p) => [$p->agreement_number, $p->title, $p->company?->name ?? '', $p->start_date?->format('Y-m-d') ?? '', $p->end_date?->format('Y-m-d') ?? ''],
        )->send();
    }

    public function downloadTemplate(CsvHandler $csv): mixed
    {
        return $csv->downloadTemplate(
            [__('partnership.agreement_number'), __('partnership.title_field'), __('partnership.start_date'), __('partnership.end_date')],
            ['421/PKS/2025', __('partnership.title_placeholder'), now()->format('Y-m-d'), now()->addYear()->format('Y-m-d')],
            'partnerships-template.csv',
        )->send();
    }

    private function uploadMouDocument(Partnership $partnership): void
    {
        if ($this->mouDocument) {
            $partnership->addMedia($this->mouDocument->getRealPath())
                ->usingFileName($this->mouDocument->getClientOriginalName())
                ->toMediaCollection(Partnership::COLLECTION_MOU);
            $this->mouDocument = null;
        }
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        return view('partnership.partnership-manager');
    }
}
