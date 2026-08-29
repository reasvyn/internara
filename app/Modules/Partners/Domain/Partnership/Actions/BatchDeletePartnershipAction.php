<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Partnership\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;

final class BatchDeletePartnershipAction extends BaseCommandAction
{
    public function __construct(protected readonly DeletePartnershipAction $deleteAction) {}

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

            $this->log('partnerships_batch_deleted', null, [
                'deleted_count' => $deleted,
                'blocked_count' => $blocked,
            ]);

            return ['deleted' => $deleted, 'blocked' => $blocked];
        });
    }
}
