<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\UI\Livewire\Concerns\ManagesRecords;

/**
 * Class CompanyManager
 *
 * Manages industry partner master data.
 */
class CompanyManager extends Component
{
    use ManagesRecords;

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

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('company.view');
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
