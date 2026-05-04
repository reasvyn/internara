<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Detects potential duplicate/cloned accounts.
 *
 * S1 - Secure: Prevents account cloning attacks.
 * S2 - Sustain: Efficient single-query detection with logging.
 */
class DetectUserAccountCloneAction
{
    /**
     * Detect suspected cloned or duplicate accounts.
     *
     * @return Collection<int, array{type: string, identifier: string, user_ids: list<string>}>
     */
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
            Log::error('Failed to detect cloned accounts', [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }
}
