<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Partnership\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\Partners\Domain\Partnership\Actions\CreatePartnershipAction;
use App\Modules\Partners\Domain\Partnership\Actions\DeletePartnershipAction;
use App\Modules\Partners\Domain\Partnership\Actions\TerminatePartnershipAction;
use App\Modules\Partners\Domain\Partnership\Actions\UpdatePartnershipAction;
use App\Modules\Partners\Domain\Partnership\Data\PartnershipData;
use App\Modules\Partners\Domain\Partnership\Enums\PartnershipStatus;
use App\Modules\Partners\Domain\Partnership\Livewire\Forms\PartnershipForm;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

class PartnershipManager extends BaseRecordManager
{
    use Interactions;
    use WithFileUploads;

    public bool $showModal = false;

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
            [
                'index' => 'agreement_number',
                'label' => __('partnership.agreement_number'),
                'sortable' => true,
            ],
            ['index' => 'company_name', 'label' => __('partnership.company'), 'sortable' => true],
            ['index' => 'title', 'label' => __('partnership.title_field'), 'sortable' => true],
            ['index' => 'start_date', 'label' => __('partnership.start_date'), 'sortable' => true],
            ['index' => 'end_date', 'label' => __('partnership.end_date'), 'sortable' => true],
            ['index' => 'status', 'label' => __('partnership.status'), 'sortable' => true],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
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
        return $query->where(function (Builder $q) {
            $q->where('partnerships.agreement_number', 'like', "%{$this->search}%")
                ->orWhere('partnerships.title', 'like', "%{$this->search}%")
                ->orWhere('companies.name', 'like', "%{$this->search}%");
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when(
                $this->filters['status'] ?? null,
                fn ($q, $v) => $q->where('partnerships.status', $v),
            )
            ->when(
                $this->filters['company_id'] ?? null,
                fn ($q, $v) => $q->where('partnerships.company_id', $v),
            );
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
        $this->authorize('create', Partnership::class);
        $this->resetErrorBag();
        $this->form->reset();
        $this->form->id = null;
        $this->mouDocument = null;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $partnership = Partnership::findOrFail($id);
        $this->authorize('update', $partnership);

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

        $data = PartnershipData::from($this->form->toArray());

        if ($this->form->id) {
            $partnership = Partnership::findOrFail($this->form->id);
            $this->authorize('update', $partnership);
            $update->execute($partnership, $data);
            $this->uploadMouDocument($partnership);
            $this->toast()->success(__('partnership.update_success'))->send();
        } else {
            $this->authorize('create', Partnership::class);
            $partnership = $create->execute($data);
            $this->uploadMouDocument($partnership);
            $this->toast()->success(__('partnership.save_success'))->send();
        }

        $this->showModal = false;
    }

    // --- Direct Actions ---

    public function terminate(string $id, TerminatePartnershipAction $terminateAction): void
    {
        $partnership = Partnership::findOrFail($id);
        $this->authorize('update', $partnership);
        $terminateAction->execute($partnership);
        $this->toast()->success(__('partnership.terminate_success'))->send();
    }

    // --- Confirm Dialog ---

    public function askDelete(string $id): void
    {
        $partnership = Partnership::findOrFail($id);
        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->confirmMessage = __('partnership.delete_confirm');
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function askTerminate(string $id): void
    {
        $partnership = Partnership::findOrFail($id);
        $this->confirmTarget = $id;
        $this->confirmType = 'terminate';
        $this->confirmMessage = __('partnership.terminate_confirm');
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function askDeleteSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $this->confirmType = 'delete_selected';
        $this->confirmMessage = __('partnership.delete_selected_confirm');
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
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
            $this->toast()->error($e->getMessage())->send();
        }
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    private function executeDelete(string $id, DeletePartnershipAction $action): void
    {
        $partnership = Partnership::findOrFail($id);
        $this->authorize('delete', $partnership);
        $action->execute($partnership);
        $this->toast()->success(__('partnership.delete_success'))->send();
    }

    private function executeTerminate(string $id, TerminatePartnershipAction $action): void
    {
        $partnership = Partnership::findOrFail($id);
        $action->execute($partnership);
        $this->toast()->success(__('partnership.terminate_success'))->send();
    }

    private function executeDeleteSelected(BatchDeletePartnershipAction $action): void
    {
        $result = $action->execute($this->selectedIds);

        if ($result['deleted'] > 0) {
            $this->toast()->success(
                __('common.actions.bulk_action_done', [
                    'count' => $result['deleted'],
                    'action' => __('common.actions.delete'),
                ]),
            )->send();
        }

        if ($result['blocked'] > 0) {
            $this->toast()->warning(
                __('partnership.delete_blocked_bulk', ['count' => $result['blocked']]),
            )->send();
        }

        $this->clearSelection();
    }

    private function uploadMouDocument(Partnership $partnership): void
    {
        if ($this->mouDocument) {
            $partnership
                ->addMedia($this->mouDocument->getRealPath())
                ->usingFileName($this->mouDocument->getClientOriginalName())
                ->toMediaCollection(Partnership::COLLECTION_MOU);
            $this->mouDocument = null;
        }
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('partners.partnership.partnership-manager');
    }
}
