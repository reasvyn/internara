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
     * Set the model class.
     */
    public function boot(
        \Modules\Internship\Services\Contracts\CompanyService $companyService,
    ): void {
        $this->service = $companyService;
    }

    public function initialize(): void {}

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
     * Get records for the table.
     */
    #[Computed]
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->service->paginate(
            [
                'search' => $this->search,
                'sort_by' => $this->sortBy['column'] ?? 'created_at',
                'sort_dir' => $this->sortBy['direction'] ?? 'desc',
            ],
            $this->perPage,
        );
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
