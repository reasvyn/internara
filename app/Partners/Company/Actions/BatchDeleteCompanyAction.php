<?php

declare(strict_types=1);

namespace App\Partners\Company\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Partners\Company\Models\Company;

final class BatchDeleteCompanyAction extends BaseCommandAction
{
    public function __construct(protected readonly DeleteCompanyAction $deleteAction) {}

    /**
     * @param string[] $ids
     *
     * @return array{deleted: int, blocked: int}
     */
    public function execute(array $ids): array
    {
        return $this->transaction(function () use ($ids) {
            $deleted = 0;
            $blocked = 0;

            foreach ($ids as $id) {
                $company = Company::withCount(['placements', 'partnerships'])->find($id);

                if (! $company) {
                    continue;
                }

                if (! $company->asCompanyState()->canBeDeleted()) {
                    $blocked++;

                    continue;
                }

                $this->deleteAction->execute($company);
                $deleted++;
            }

            $this->log('companies_batch_deleted', null, [
                'deleted_count' => $deleted,
                'blocked_count' => $blocked,
            ]);

            return ['deleted' => $deleted, 'blocked' => $blocked];
        });
    }
}
