<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\AccountStatus\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;

class DetectUserAccountCloneAction extends BaseAction
{
    public function execute(): Collection
    {
        try {
            return User::query()
                ->select('email')
                ->selectRaw('GROUP_CONCAT(id) as user_ids')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('email')
                ->havingRaw('count > 1')
                ->get()
                ->map(fn ($row) => [
                    'type' => 'duplicate_email',
                    'identifier' => $row->email,
                    'user_ids' => explode(',', $row->user_ids),
                ]);
        } catch (\Throwable $e) {
            SmartLogger::error('Failed to detect cloned accounts')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();

            return collect();
        }
    }
}
