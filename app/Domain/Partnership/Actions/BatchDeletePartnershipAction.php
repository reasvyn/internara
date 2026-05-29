<?php

declare(strict_types=1);

namespace App\Domain\Partnership\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Partnership\Models\Partnership;

final class BatchDeletePartnershipAction extends BaseAction
{
    public function __construct(
        protected readonly DeletePartnershipAction $deleteAction,
    ) {}

    /**
     * @param string[] $ids
     * @return array{deleted: int, blocked: int}
     */
    public function execute(array $ids): array
    {
        $deleted = 0;
        $blocked = 0;

        foreach ($ids as $id) {
            $partnership = Partnership::find($id);

            if (! $partnership) {
                continue;
            }

            if (! $partnership->asPartnershipState()->canBeDeleted()) {
                $blocked++;

                continue;
            }

            $this->deleteAction->execute($partnership);
            $deleted++;
        }

        return ['deleted' => $deleted, 'blocked' => $blocked];
    }
}
