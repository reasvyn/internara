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
use App\Domain\Partnership\Livewire\Forms\PartnershipForm;
use App\Domain\Partnership\Models\Company;
use App\Domain\Partnership\Models\Partnership;
use App\Domain\Shared\Support\CsvHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public PartnershipForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', Partnership::class);
    }

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
            'total' => Partnership::count(),
            'active' => $active,
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
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
        $this->form->reset();
        $this->form->id = null;
        $this->mouDocument = null;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $partnership = Partnership::findOrFail($id);

        $this->resetErrorBag();
        $this->form->id = $partnership->id;
        $this->form->company_id = $partnership->company_id;
        $this->form->agreement_number = $partnership->agreement_number;
        $this->form->title = $partnership->title;
        $this->form->start_date = $partnership->start_date?->format('Y-m-d') ?? '';
        $this->form->end_date = $partnership->end_date?->format('Y-m-d') ?? '';
        $this->form->scope = $partnership->scope ?? '';
        $this->form->contact_person_name = $partnership->contact_person_name ?? '';
        $this->form->contact_person_phone = $partnership->contact_person_phone ?? '';
        $this->form->contact_person_email = $partnership->contact_person_email ?? '';
        $this->form->signed_by_school = $partnership->signed_by_school ?? '';
        $this->form->signed_by_company = $partnership->signed_by_company ?? '';
        $this->form->signed_at = $partnership->signed_at?->format('Y-m-d') ?? '';
        $this->form->notes = $partnership->notes ?? '';
        $this->showModal = true;
    }

    public function save(CreatePartnershipAction $create, UpdatePartnershipAction $update): void
    {
        $this->form->validate();

        if ($this->form->id) {
            $partnership = Partnership::findOrFail($this->form->id);
            $update->execute($partnership, $this->form->toArray());
            $this->uploadMouDocument($partnership);
            flash()->success(__('partnership.update_success'));
        } else {
            $partnership = $create->execute($this->form->toArray());
            $this->uploadMouDocument($partnership);
            flash()->success(__('partnership.save_success'));
        }

        $this->showModal = false;
    }

    // --- Direct Actions ---

    public function terminate(string $id, TerminatePartnershipAction $terminateAction): void
    {
        $partnership = Partnership::findOrFail($id);
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
        BatchDeletePartnershipAction $batchDelete,
    ): void {
        if ($this->confirmTarget === null && $this->confirmType !== 'delete_selected') {
            return;
        }

        try {
            match ($this->confirmType) {
                'delete' => $this->executeDelete($this->confirmTarget, $deleteAction),
                'terminate' => $this->executeTerminate($this->confirmTarget, $terminateAction),
                'delete_selected' => $this->executeDeleteSelected($batchDelete),
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
        $action->execute($partnership);
        flash()->success(__('partnership.delete_success'));
    }

    private function executeTerminate(string $id, TerminatePartnershipAction $action): void
    {
        $partnership = Partnership::findOrFail($id);
        $action->execute($partnership);
        flash()->success(__('partnership.terminate_success'));
    }

    private function executeDeleteSelected(BatchDeletePartnershipAction $action): void
    {
        $result = $action->execute($this->selectedIds);

        if ($result['deleted'] > 0) {
            flash()->success(__('common.actions.bulk_action_done', [
                'count' => $result['deleted'],
                'action' => __('common.actions.delete'),
            ]));
        }

        if ($result['blocked'] > 0) {
            flash()->warning(__('partnership.delete_blocked_bulk', ['count' => $result['blocked']]));
        }

        $this->clearSelection();
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
