<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\Computed;

/**
 * Trait WithRecordSelection
 *
 * Provides standardized record selection logic for bulk actions.
 */
trait WithRecordSelection
{
    /**
     * Selected record IDs.
     */
    public array $selectedIds = [];

    /**
     * Clear all selected records.
     */
    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    /**
     * Select all records.
     * Note: This usually only selects IDs on the current page.
     */
    public function selectAll(array $ids): void
    {
        $this->selectedIds = $ids;
    }

    /**
     * Get the count of selected records.
     */
    #[Computed]
    public function selected_count(): int
    {
        return count($this->selectedIds);
    }
}
