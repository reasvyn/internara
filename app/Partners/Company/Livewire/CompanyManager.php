<?php

declare(strict_types=1);

namespace App\Partners\Company\Livewire;

use App\Core\Enums\CsvRowResult;
use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Core\Support\CsvHandler;
use App\Enrollment\Placement\Models\Placement;
use App\Partners\Company\Actions\BatchDeleteCompanyAction;
use App\Partners\Company\Actions\CreateCompanyAction;
use App\Partners\Company\Actions\DeleteCompanyAction;
use App\Partners\Company\Actions\UpdateCompanyAction;
use App\Partners\Company\Data\CompanyData;
use App\Partners\Company\Livewire\Forms\CompanyForm;
use App\Partners\Company\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyManager extends BaseRecordManager
{
    use WithFileUploads;

    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public $importFile = null;

    public CompanyForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', Company::class);
    }

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
        return $query->where(function ($q) {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('industry_sector', 'like', "%{$this->search}%");
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when(
                $this->filters['industry_sector'] ?? null,
                fn ($q, $v) => $q->where('industry_sector', 'like', "%{$v}%"),
            )
            ->when(
                $this->filters['phone'] ?? null,
                fn ($q, $v) => $q->where('phone', 'like', "%{$v}%"),
            )
            ->when(
                $this->filters['has_placements'] ?? null,
                fn ($q, $v) => $v === 'yes' ? $q->has('placements') : $q->doesntHave('placements'),
            );
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Company::count(),
            'with_placements' => Company::whereHas('placements')
                ->orWhereHas('partnerships')
                ->count(),
            'active_partnerships' => Company::whereHas(
                'partnerships',
                fn ($q) => $q->where('status', 'active'),
            )->count(),
            'available_slots' => Placement::query()
                ->selectRaw('SUM(quota - filled_quota) as available')
                ->value('available') ?? 0,
        ];
    }

    // --- CRUD ---

    public function create(): void
    {
        $this->authorize('create', Company::class);
        $this->resetErrorBag();
        $this->form->reset();
        $this->form->id = null;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $company = Company::findOrFail($id);
        $this->authorize('update', $company);

        $this->resetErrorBag();
        $this->form->id = $company->id;
        $this->form->name = $company->name;
        $this->form->address = $company->address ?? '';
        $this->form->phone = $company->phone ?? '';
        $this->form->email = $company->email ?? '';
        $this->form->website = $company->website ?? '';
        $this->form->description = $company->description ?? '';
        $this->form->industry_sector = $company->industry_sector ?? '';
        $this->showModal = true;
    }

    public function save(CreateCompanyAction $create, UpdateCompanyAction $update): void
    {
        $this->form->validate();

        $dto = new CompanyData(
            name: $this->form->name,
            address: $this->form->address ?: null,
            phone: $this->form->phone,
            email: $this->form->email,
            website: $this->form->website,
            description: $this->form->description,
            industrySector: $this->form->industry_sector,
        );

        if ($this->form->id) {
            $company = Company::findOrFail($this->form->id);
            $this->authorize('update', $company);
            $update->execute($company, $dto);
            flash()->success(__('company.update_success'));
        } else {
            $this->authorize('create', Company::class);
            $create->execute($dto);
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
        $this->confirmMessage = __('company.delete_selected_confirm', [
            'count' => count($this->selectedIds),
        ]);
        $this->showConfirm = true;
    }

    public function confirmAction(
        DeleteCompanyAction $deleteAction,
        BatchDeleteCompanyAction $batchDelete,
    ): void {
        if ($this->confirmTarget === null && $this->confirmType !== 'delete_selected') {
            return;
        }

        try {
            match ($this->confirmType) {
                'delete' => $this->executeDelete($this->confirmTarget, $deleteAction),
                'delete_selected' => $this->executeDeleteSelected($batchDelete),
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
        $this->authorize('delete', $company);
        $action->execute($company);
        flash()->success(__('company.delete_success'));
    }

    private function executeDeleteSelected(BatchDeleteCompanyAction $action): void
    {
        $result = $action->execute($this->selectedIds);

        if ($result['deleted'] > 0) {
            flash()->success(
                __('common.actions.bulk_action_done', [
                    'count' => $result['deleted'],
                    'action' => __('common.actions.delete'),
                ]),
            );
        }

        if ($result['blocked'] > 0) {
            flash()->warning(__('company.delete_blocked_bulk', ['count' => $result['blocked']]));
        }

        $this->clearSelection();
    }

    // --- Import / Export ---

    public function updatedImportFile(): void
    {
        if ($this->importFile) {
            $this->import(app(CsvHandler::class), app(CreateCompanyAction::class));
        }
    }

    public function import(CsvHandler $csv, CreateCompanyAction $create): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $csv->import($this->importFile->getRealPath(), function (array $row) use (
            $create,
        ) {
            $name = trim($row[0] ?? '');

            if ($name === '') {
                return null;
            }

            if (Company::where('name', $name)->exists()) {
                return CsvRowResult::SKIPPED;
            }

            $create->execute(CompanyData::from([
                'name' => $name,
                'address' => trim($row[1] ?? '') ?: null,
                'phone' => trim($row[2] ?? '') ?: null,
                'email' => trim($row[3] ?? '') ?: null,
                'website' => trim($row[4] ?? '') ?: null,
                'description' => trim($row[5] ?? '') ?: null,
                'industry_sector' => trim($row[6] ?? '') ?: null,
            ]));

            return CsvRowResult::CREATED;
        });

        $this->importFile = null;

        if ($result['invalid']) {
            flash()->error(__('common.actions.import_invalid'));

            return;
        }

        flash()->success(
            __('common.actions.import_summary', [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
            ]),
        );
    }

    public function export(CsvHandler $csv): StreamedResponse
    {
        $companies = Company::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return $csv->export(
            $companies,
            [
                __('common.name'),
                __('common.address'),
                __('common.phone'),
                __('common.email'),
                __('common.website'),
                __('common.description'),
                __('company.industry_sector'),
            ],
            fn ($c) => [
                $c->name,
                $c->address ?? '',
                $c->phone ?? '',
                $c->email ?? '',
                $c->website ?? '',
                $c->description ?? '',
                $c->industry_sector ?? '',
            ],
            'companies.csv',
        );
    }

    public function exportSelected(CsvHandler $csv): ?StreamedResponse
    {
        if ($this->selectedIds === []) {
            flash()->warning(__('common.actions.no_records_selected'));

            return null;
        }

        $companies = Company::whereIn('id', $this->selectedIds)->orderBy('name')->get();

        return $csv->export(
            $companies,
            [
                __('common.name'),
                __('common.address'),
                __('common.phone'),
                __('common.email'),
                __('common.website'),
                __('common.description'),
                __('company.industry_sector'),
            ],
            fn ($c) => [
                $c->name,
                $c->address ?? '',
                $c->phone ?? '',
                $c->email ?? '',
                $c->website ?? '',
                $c->description ?? '',
                $c->industry_sector ?? '',
            ],
            'companies-selected.csv',
        );
    }

    public function downloadTemplate(CsvHandler $csv): StreamedResponse
    {
        return $csv->downloadTemplate(
            [
                __('common.name'),
                __('common.address'),
                __('common.phone'),
                __('common.email'),
                __('common.website'),
                __('common.description'),
                __('company.industry_sector'),
            ],
            [
                __('company.name_placeholder'),
                __('company.address_placeholder'),
                __('company.phone_placeholder'),
                __('company.email_placeholder'),
                __('company.website_placeholder'),
                '',
                __('company.industry_sector_placeholder'),
            ],
            'companies-template.csv',
        );
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('partners.company.company-manager');
    }
}
