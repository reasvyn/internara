<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\UI\Livewire\RecordManager;

/**
 * Class CompanyManager
 *
 * Manages industry partner master data.
 */
class CompanyManager extends RecordManager
{
    protected string $viewPermission = 'internship.manage';

    /**
     * Component properties.
     */
    public array $form = [
        'id' => null,
        'name' => '',
        'address' => '',
        'business_field' => '',
        'phone' => '',
        'fax' => '',
        'email' => '',
        'leader_name' => '',
    ];

    /**
     * Filter state
     */
    public string $filterBusinessField = '';

    public string $filterEmail = '';

    public array $selectedRecords = [];

    public bool $selectAll = false;

    public string $massAction = '';

    /**
     * Set the model class.
     */
    public function boot(
        \Modules\Internship\Services\Contracts\CompanyService $companyService,
    ): void {
        $this->service = $companyService;
    }

    public function initialize(): void {}

    /**
     * Reset form array
     */
    public function add(): void
    {
        $this->form = [
            'id' => null,
            'name' => '',
            'address' => '',
            'business_field' => '',
            'phone' => '',
            'fax' => '',
            'email' => '',
            'leader_name' => '',
        ];
        $this->toggleModal(self::MODAL_FORM, true);
    }

    /**
     * Get business field filter options
     */
    #[Computed]
    public function businessFieldOptions()
    {
        return $this->service->query()
            ->whereNotNull('business_field')
            ->distinct('business_field')
            ->pluck('business_field')
            ->map(fn ($field) => ['value' => $field, 'label' => $field])
            ->values();
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->filterBusinessField = '';
        $this->filterEmail = '';
        $this->selectAll = false;
        $this->selectedRecords = [];
    }

    /**
     * Get active filter count
     */
    #[Computed]
    public function activeFilterCount(): int
    {
        $count = 0;
        if ($this->filterBusinessField) {
            $count++;
        }
        if ($this->filterEmail) {
            $count++;
        }

        return $count;
    }

    /**
     * Select/deselect all records
     */
    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedRecords = $this->records->pluck('id')->toArray();
        } else {
            $this->selectedRecords = [];
        }
    }

    /**
     * Execute mass action
     */
    public function executeMassAction(): void
    {
        if (empty($this->selectedRecords) || !$this->massAction) {
            $this->dispatch('notify', type: 'warning', message: __('internship::ui.select_items_for_action'));

            return;
        }

        match ($this->massAction) {
            'delete' => $this->massBulkDelete(),
            'export' => $this->massExport(),
            default => null,
        };
    }

    /**
     * Mass delete companies
     */
    protected function massBulkDelete(): void
    {
        try {
            $deleted = 0;
            foreach ($this->selectedRecords as $id) {
                try {
                    $this->service->delete($id);
                    $deleted++;
                } catch (\Exception $e) {
                    // Continue with next record
                }
            }

            $this->selectedRecords = [];
            $this->selectAll = false;
            $this->massAction = '';

            $this->dispatch('notify', type: 'success', message: __('internship::ui.bulk_delete_success', ['count' => $deleted]));
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Mass export companies
     */
    protected function massExport(): void
    {
        try {
            // Export selected companies as CSV
            $companies = $this->service->query()
                ->whereIn('id', $this->selectedRecords)
                ->get();

            $csv = "Name,Business Field,Phone,Email,Address,Leader\n";
            foreach ($companies as $company) {
                $csv .= "\"{$company->name}\",\"{$company->business_field}\",\"{$company->phone}\",\"{$company->email}\",\"{$company->address}\",\"{$company->leader_name}\"\n";
            }

            $this->dispatch('download-csv', content: $csv, filename: "companies_" . date('Y-m-d_His') . ".csv");
            $this->dispatch('notify', type: 'success', message: __('internship::ui.export_success'));

            $this->selectedRecords = [];
            $this->selectAll = false;
            $this->massAction = '';
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('ui::common.name'), 'sortable' => true],
            ['key' => 'business_field', 'label' => __('internship::ui.business_field'), 'sortable' => false],
            ['key' => 'phone', 'label' => __('ui::common.phone'), 'sortable' => false],
            ['key' => 'email', 'label' => __('ui::common.email'), 'sortable' => false],
            ['key' => 'created_at', 'label' => __('ui::common.created_at'), 'sortable' => true],
        ];
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        parent::mount();
    }

    /**
     * Get records for the table with filters applied.
     */
    #[Computed]
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->service->query();

        // Apply filters
        if ($this->filterBusinessField) {
            $query->where('business_field', $this->filterBusinessField);
        }
        if ($this->filterEmail) {
            $query->where('email', 'like', "%{$this->filterEmail}%");
        }

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('business_field', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        // Apply sorting
        $sortBy = $this->sortBy['column'] ?? 'created_at';
        $sortDir = $this->sortBy['direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($this->perPage);
    }

    /**
     * Rules for validation.
     */
    protected function rules(): array
    {
        return [
            'form.name' => 'required|string|max:255',
            'form.address' => 'nullable|string',
            'form.business_field' => 'nullable|string|max:255',
            'form.phone' => 'nullable|string|max:50',
            'form.fax' => 'nullable|string|max:50',
            'form.email' => 'nullable|email|max:255',
            'form.leader_name' => 'nullable|string|max:255',
        ];
    }

    public function render(): View
    {
        return view('internship::livewire.company-manager', [
            'records' => $this->records,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('internship::ui.company_title').
                ' | '.
                setting('brand_name', setting('app_name')),
        ]);
    }
}
