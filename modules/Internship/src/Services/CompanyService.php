<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Internship\Models\Company;
use Modules\Internship\Services\Contracts\CompanyService as Contract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Class CompanyService
 *
 * Handles the business logic for industry partner master data.
 */
class CompanyService extends EloquentQuery implements Contract
{
    /**
     * CompanyService constructor.
     */
    public function __construct(Company $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'business_field', 'email']);
        $this->setSortable(['name', 'created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        return [
            'total' => $this->count(),
            'active_partners' => $this->model->newQuery()->where('is_active', true)->count(),
            'with_mentors' => $this->model->newQuery()->has('mentors')->count(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Company
    {
        Gate::authorize('create', Company::class);

        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Company $company, array $data): void
    {
        parent::update($company, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Company $company): bool
    {
        return parent::delete($company);
    }
}
