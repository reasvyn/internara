<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Livewire\Mocks;

use App\Core\Livewire\BaseRecordManager;
use App\Core\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;

class TestRecordManager extends BaseRecordManager
{
    public function headers(): array
    {
        return ['ID'];
    }

    protected function query(): Builder
    {
        return ActivityLog::query();
    }

    public function render()
    {
        return '<div>Test Component</div>';
    }
}
