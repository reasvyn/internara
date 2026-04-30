<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Company;

use App\Actions\Company\CreateCompanyAction;
use App\Actions\Company\DeleteCompanyAction;
use App\Actions\Company\UpdateCompanyAction;
use App\Models\InternshipCompany;
use App\Models\InternshipPlacement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyIndex extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public string $companyId = '';
    public string $name = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $website = '';
    public string $description = '';
    public string $industry_sector = '';

    public string $search = '';

    protected $queryString = ['search'];

    #[Computed]
    public function stats(): array
    {
        $companies = InternshipCompany::query();

        return [
            'total' => $companies->count(),
            'with_placements' => InternshipCompany::whereHas('placements')->count(),
            'available_slots' => InternshipPlacement::query()
                ->selectRaw('SUM(quota - filled_quota) as available')
                ->value('available') ?? 0,
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:internship_companies,name,' . $this->companyId],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'industry_sector' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function create(): void
    {
        $this->reset(['companyId', 'name', 'address', 'phone', 'email', 'website', 'description', 'industry_sector']);
        $this->showModal = true;
    }

    public function edit(InternshipCompany $company): void
    {
        $this->companyId = $company->id;
        $this->name = $company->name;
        $this->address = $company->address ?? '';
        $this->phone = $company->phone ?? '';
        $this->email = $company->email ?? '';
        $this->website = $company->website ?? '';
        $this->description = $company->description ?? '';
        $this->industry_sector = $company->industry_sector ?? '';
        $this->showModal = true;
    }

    public function save(CreateCompanyAction $create, UpdateCompanyAction $update): void
    {
        $validated = $this->validate();

        if ($this->companyId) {
            $company = InternshipCompany::findOrFail($this->companyId);
            $update->execute($company, $validated);
            flash()->success(__('company.update_success'));
        } else {
            $create->execute($validated);
            flash()->success(__('company.save_success'));
        }

        $this->showModal = false;
        $this->reset(['companyId', 'name', 'address', 'phone', 'email', 'website', 'description', 'industry_sector']);
    }

    public function delete(InternshipCompany $company, DeleteCompanyAction $deleteAction): void
    {
        if ($company->placements()->exists()) {
            flash()->error(__('company.delete_blocked'));
            return;
        }

        $deleteAction->execute($company);
        flash()->success(__('company.delete_success'));
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $companies = InternshipCompany::query()
            ->withCount('placements')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.company.company-index', [
            'companies' => $companies,
        ]);
    }
}
