<?php

declare(strict_types=1);

namespace App\Core\Livewire\Concerns;

use Livewire\Attributes\Computed;

trait WithRecordSelection
{
    public array $selectedIds = [];

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function selectAll(array $ids): void
    {
        $this->selectedIds = $ids;
    }

    #[Computed]
    public function selected_count(): int
    {
        return count($this->selectedIds);
    }
}
