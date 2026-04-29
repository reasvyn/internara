<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Modules\Internship\Models\Company;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * Contract for managing industry partner master data.
 *
 * @extends EloquentQuery<Company>
 */
interface CompanyService extends EloquentQuery
{
    /**
     * Get summary metrics for industry partners.
     *
     * @return array<string, int>
     */
    public function getStats(): array;

    /**
     * Update an existing company.
     */
    public function update(Company $company, array $data): void;

    /**
     * Delete a company.
     */
    public function delete(Company $company): bool;
}
