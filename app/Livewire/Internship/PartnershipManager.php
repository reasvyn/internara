<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Actions\Internship\CreatePartnershipAction;
use App\Actions\Internship\DeletePartnershipAction;
use App\Actions\Internship\TerminatePartnershipAction;
use App\Actions\Internship\UpdatePartnershipAction;
use App\Livewire\Core\BaseRecordManager;
use App\Models\Company;
use App\Models\Partnership;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

class PartnershipManager extends BaseRecordManager
{
    use WithFileUploads;

    public bool $showModal = false;

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
            ['key' => 'title', 'label' => __('partnership.title'), 'sortable' => true],
            ['key' => 'start_date', 'label' => __('partnership.start_date'), 'sortable' => true],
            ['key' => 'end_date', 'label' => __('partnership.end_date'), 'sortable' => true],
            ['key' => 'status', 'label' => __('partnership.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Partnership::query()
            ->select(['partnerships.*', 'internship_companies.name as company_name'])
            ->join('internship_companies', 'partnerships.company_id', '=', 'internship_companies.id');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q) {
                $q->where('partnerships.agreement_number', 'like', "%{$this->search}%")
                    ->orWhere('partnerships.title', 'like', "%{$this->search}%")
                    ->orWhere('internship_companies.name', 'like', "%{$this->search}%");
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
            'formData.company_id' => ['required', 'exists:internship_companies,id'],
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

    private function uploadMouDocument(Partnership $partnership): void
    {
        if ($this->mouDocument) {
            $partnership->addMedia($this->mouDocument->getRealPath())
                ->usingFileName($this->mouDocument->getClientOriginalName())
                ->toMediaCollection(Partnership::COLLECTION_MOU);
            $this->mouDocument = null;
        }
    }

    public function delete(Partnership $partnership, DeletePartnershipAction $deleteAction): void
    {
        if (! $partnership->asPartnershipState()->canBeDeleted()) {
            flash()->error(__('partnership.delete_blocked'));

            return;
        }

        $deleteAction->execute($partnership);
        flash()->success(__('partnership.delete_success'));
    }

    public function deleteSelected(DeletePartnershipAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $partnership = Partnership::find($id);
            if ($partnership && $partnership->asPartnershipState()->canBeDeleted()) {
                $deleteAction->execute($partnership);
            }
        });
    }

    public function terminate(Partnership $partnership, TerminatePartnershipAction $terminateAction): void
    {
        $terminateAction->execute($partnership);
        flash()->success(__('partnership.terminate_success'));
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.internship.partnership-manager');
    }
}
