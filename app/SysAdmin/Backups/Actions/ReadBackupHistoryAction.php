<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Actions;

use App\Core\Actions\BaseReadAction;
use App\SysAdmin\Backups\Models\Backup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ReadBackupHistoryAction extends BaseReadAction
{
    public function __construct(protected readonly Backup $model) {}

    public function execute(int $perPage = 20, ?string $type = null, ?string $status = null): LengthAwarePaginator
    {
        return $this->model
            ->with('creator')
            ->when($type, fn ($q, $t) => $q->where('type', $t))
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($perPage);
    }
}
