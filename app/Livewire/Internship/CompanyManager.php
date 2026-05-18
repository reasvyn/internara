<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Actions\Internship\CreateCompanyAction;
use App\Actions\Internship\DeleteCompanyAction;
use App\Actions\Internship\UpdateCompanyAction;
use App\Exceptions\RejectedException;
use App\Livewire\Core\BaseRecordManager;
use App\Models\Company;
use App\Models\Placement;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class CompanyManager extends BaseRecordManager
{
    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public array $formData = [
        'id' => null,
        'name' => '',
        'address' => '',
        'phone' => '',
        'email' => '',
        'website' => '',
        'description' => '',
        'industry_sector' => '',
    ];

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('company.name'), 'sortable' => true],
            ['key' => 'industry_sector', 'label' => __('company.industry'), 'sortable' => true],
            ['key' => 'address', 'label' => __('company.address')],
            ['key' => 'placements_count', 'label' => __('company.placements_count')],
            ['key' => 'partnerships_count', 'label' => __('company.partnerships_count')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Company::query()->withCount(['placements', 'partnerships']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query
            ->where('name', 'like', "%{$this->search}%")
            ->orWhere('industry_sector', 'like', "%{$this->search}%");
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query->when($this->filters['industry_sector'] ?? null, fn ($q, $v) => $q->where('industry_sector', 'like', "%{$v}%"));
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Company::count(),
            'with_placements' => Company::whereHas('placements')->orWhereHas('partnerships')->count(),
            'available_slots' => Placement::query()
                ->selectRaw('SUM(quota - filled_quota) as available')
                ->value('available') ?? 0,
        ];
    }

    // --- CRUD ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => null,
            'name' => '',
            'address' => '',
            'phone' => '',
            'email' => '',
            'website' => '',
            'description' => '',
            'industry_sector' => '',
        ];
        $this->showModal = true;
    }

    public function edit(Company $company): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => $company->id,
            'name' => $company->name,
            'address' => $company->address ?? '',
            'phone' => $company->phone ?? '',
            'email' => $company->email ?? '',
            'website' => $company->website ?? '',
            'description' => $company->description ?? '',
            'industry_sector' => $company->industry_sector ?? '',
        ];
        $this->showModal = true;
    }

    public function save(CreateCompanyAction $create, UpdateCompanyAction $update): void
    {
        $this->validate([
            'formData.name' => [
                'required',
                'string',
                'max:255',
                'unique:internship_companies,name,'.($this->formData['id'] ?? 'NULL'),
            ],
            'formData.address' => ['required', 'string'],
            'formData.phone' => ['nullable', 'string', 'max:20'],
            'formData.email' => ['nullable', 'email', 'max:255'],
            'formData.website' => ['nullable', 'url', 'max:255'],
            'formData.description' => ['nullable', 'string'],
            'formData.industry_sector' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->formData['id']) {
            $company = Company::findOrFail($this->formData['id']);
            $update->execute($company, $this->formData);
            flash()->success(__('company.update_success'));
        } else {
            $create->execute($this->formData);
            flash()->success(__('company.save_success'));
        }

        $this->showModal = false;
    }

    // --- Confirm Dialog ---

    public function askDelete(string $id): void
    {
        $company = Company::findOrFail($id);
        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->confirmMessage = __('company.confirm_delete', ['name' => $company->name]);
        $this->showConfirm = true;
    }

    public function askDeleteSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $this->confirmType = 'delete_selected';
        $this->confirmMessage = __('company.delete_selected_confirm', ['count' => count($this->selectedIds)]);
        $this->showConfirm = true;
    }

    public function confirmAction(DeleteCompanyAction $deleteAction): void
    {
        if ($this->confirmTarget === null && $this->confirmType !== 'delete_selected') {
            return;
        }

        try {
            match ($this->confirmType) {
                'delete' => $this->executeDelete($this->confirmTarget, $deleteAction),
                'delete_selected' => $this->executeDeleteSelected($deleteAction),
                default => null,
            };
        } catch (RejectedException) {
            flash()->error(__('company.delete_blocked'));
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    private function executeDelete(string $id, DeleteCompanyAction $action): void
    {
        $company = Company::findOrFail($id);

        if (! $company->asCompanyState()->canBeDeleted()) {
            flash()->error(__('company.delete_blocked'));

            return;
        }

        $action->execute($company);
        flash()->success(__('company.delete_success'));
    }

    private function executeDeleteSelected(DeleteCompanyAction $action): void
    {
        $count = 0;

        foreach ($this->selectedIds as $id) {
            $company = Company::find($id);

            if ($company && $company->asCompanyState()->canBeDeleted()) {
                $action->execute($company);
                $count++;
            }
        }

        if ($count > 0) {
            flash()->success(__('common.actions.bulk_action_done', ['count' => $count, 'action' => __('common.actions.delete')]));
        }

        $this->clearSelection();
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.internship.company-manager');
    }
}
