<?php

declare(strict_types=1);

namespace App\Partners\Company\Actions;

use App\Core\Actions\BaseAction;
use App\Partners\Company\Models\Company;

final class BatchDeleteCompanyAction extends BaseAction
{
    public function __construct(protected readonly DeleteCompanyAction $deleteAction) {}

    /**
     * @param string[] $ids
     *
     * @return array{deleted: int, blocked: int}
     */
    public function execute(array $ids): array
    {
        $deleted = 0;
        $blocked = 0;

        foreach ($ids as $id) {
            $company = Company::find($id);

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

        return ['deleted' => $deleted, 'blocked' => $blocked];
    }
}
