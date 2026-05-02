<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Company;

use App\Actions\Company\CreateCompanyAction;
use App\Actions\Company\DeleteCompanyAction;
use App\Actions\Company\UpdateCompanyAction;
use App\Livewire\BaseRecordManager;
use App\Models\InternshipCompany;
use App\Models\InternshipPlacement;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

/**
 * Modernized Company Manager using BaseRecordManager pattern.
 */
class CompanyIndex extends BaseRecordManager
{
    public bool $showModal = false;

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

    /**
     * Define columns and sorting.
     */
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('company.name'), 'sortable' => true],
            ['key' => 'industry_sector', 'label' => __('company.industry'), 'sortable' => true],
            ['key' => 'address', 'label' => __('company.address')],
            ['key' => 'placements_count', 'label' => __('company.placements_count')],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Base query for companies.
     */
    protected function query(): Builder
    {
        return InternshipCompany::query()
            ->withCount('placements');
    }

    /**
     * Search implementation.
     */
    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%")
            ->orWhere('industry_sector', 'like', "%{$this->search}%");
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => InternshipCompany::count(),
            'with_placements' => InternshipCompany::whereHas('placements')->count(),
            'available_slots' => InternshipPlacement::query()
                ->selectRaw('SUM(quota - filled_quota) as available')
                ->value('available') ?? 0,
        ];
    }

    // --- Record Actions ---

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

    public function edit(InternshipCompany $company): void
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
            'formData.name' => ['required', 'string', 'max:255', 'unique:internship_companies,name,'.($this->formData['id'] ?? 'NULL')],
            'formData.address' => ['required', 'string'],
            'formData.phone' => ['nullable', 'string', 'max:20'],
            'formData.email' => ['nullable', 'email', 'max:255'],
            'formData.website' => ['nullable', 'url', 'max:255'],
            'formData.description' => ['nullable', 'string'],
            'formData.industry_sector' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->formData['id']) {
            $company = InternshipCompany::findOrFail($this->formData['id']);
            $update->execute($company, $this->formData);
            $this->success(__('company.update_success'));
        } else {
            $create->execute($this->formData);
            $this->success(__('company.save_success'));
        }

        $this->showModal = false;
    }

    public function delete(InternshipCompany $company, DeleteCompanyAction $deleteAction): void
    {
        if ($company->placements()->exists()) {
            $this->error(__('company.delete_blocked'));

            return;
        }

        $deleteAction->execute($company);
        $this->success(__('company.delete_success'));
    }

    // --- Bulk Actions ---

    public function deleteSelected(DeleteCompanyAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $company = InternshipCompany::find($id);
            if ($company && ! $company->placements()->exists()) {
                $deleteAction->execute($company);
            }
        });
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.company.company-index');
    }
}
