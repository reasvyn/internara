<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Internship\Livewire\Forms\CompanyForm;
use Modules\Internship\Models\Company;
use Modules\UI\Livewire\RecordManager;

/**
 * Class CompanyManager
 *
 * Manages industry partner (company) master data with filtering and bulk operations.
 *
 * Access control:
 * - SuperAdmin/Admin: full CRUD + bulk operations
 * - Others: no access
 */
class CompanyManager extends RecordManager
{
    public CompanyForm $form;

    /**
     * Initialize the component metadata and services.
     */
    public function boot(
        \Modules\Internship\Services\Contracts\CompanyService $companyService,
    ): void {
        $this->service = $companyService;
        $this->eventPrefix = 'company';
        $this->modelClass = Company::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $this->title = __('internship::ui.company_title');
        $this->subtitle = __('internship::ui.company_subtitle');
        $this->context = 'admin::ui.menu.companies';

        $this->viewPermission = 'internship.manage';
        $this->createPermission = 'internship.manage';
        $this->updatePermission = 'internship.manage';
        $this->deletePermission = 'internship.manage';

        $this->addLabel = __('internship::ui.add_company');
        $this->deleteConfirmMessage = __('internship::ui.delete_company_confirm');
    }

    /**
     * Get summary metrics for industrial partners.
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'total' => $this->service->query()->count(),
            'fields' => $this->service->query()->distinct('business_field')->count('business_field'),
            'with_email' => $this->service->query()->whereNotNull('email')->count(),
            'latest' => $this->service->query()->where('created_at', '>=', now()->subMonth())->count(),
        ];
    }

    /**
     * Define searchable columns (client-side search).
     */
    protected array $searchable = ['name', 'email', 'phone', 'business_field'];

    /**
     * Define sortable columns.
     */
    protected array $sortable = ['name', 'email', 'created_at'];

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            [
                'key' => 'name',
                'label' => __('internship::ui.company_name'),
                'sortable' => true,
            ],
            [
                'key' => 'business_field',
                'label' => __('internship::ui.business_field'),
            ],
            [
                'key' => 'phone',
                'label' => __('ui::common.phone'),
            ],
            [
                'key' => 'email',
                'label' => __('ui::common.email'),
                'sortable' => true,
            ],
            [
                'key' => 'leader_name',
                'label' => __('internship::ui.leader_name'),
            ],
            [
                'key' => 'actions',
                'label' => '',
                'class' => 'w-1 text-right',
            ],
        ];
    }

    /**
     * Reset filters and reload.
     */
    public function resetFilters(): void
    {
        $this->filters = [];
        $this->selectedIds = [];
        $this->resetPage();
    }

    /**
     * Count active filters.
     */
    #[Computed]
    public function activeFilterCount(): int
    {
        return count(array_filter(
            $this->filters,
            fn ($v) => $v !== null && $v !== '' && $v !== [],
        ));
    }

    /**
     * Get unique business field values for filter dropdown.
     */
    #[Computed]
    public function businessFieldOptions(): array
    {
        return $this->service->query()
            ->whereNotNull('business_field')
            ->distinct('business_field')
            ->pluck('business_field')
            ->map(fn ($field) => ['id' => $field, 'name' => $field])
            ->values()
            ->toArray();
    }

    /**
     * Export headers for CSV export.
     */
    protected function getExportHeaders(): array
    {
        return [
            'name' => __('internship::ui.company_name'),
            'business_field' => __('internship::ui.business_field'),
            'phone' => __('ui::common.phone'),
            'email' => __('ui::common.email'),
            'address' => __('internship::ui.company_address'),
            'leader_name' => __('internship::ui.leader_name'),
            'created_at' => __('ui::common.created_at'),
        ];
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('internship::livewire.company-manager')
            ->layout('ui::components.layouts.dashboard', [
                'title' => $this->title . ' | ' . setting('brand_name', setting('app_name')),
            ]);
    }
}
