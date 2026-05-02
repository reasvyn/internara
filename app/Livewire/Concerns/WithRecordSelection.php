<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Trait for managing record tables with advanced features.
 *
 * S2 - Sustain: DRY approach to managerial tables.
 * S3 - Scalable: Abstracted query logic for any Eloquent model.
 */
trait WithRecordSelection
{
    /**
     * Array of selected record IDs.
     */
    public array $selectedIds = [];

    /**
     * Whether all records in the current page are selected.
     */
    public bool $selectAll = false;

    /**
     * Toggle selection for all items on the current page.
     */
    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedIds = collect($this->rows()->items())
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    /**
     * Clear selection state.
     */
    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    /**
     * Get count of selected items.
     */
    public function getSelectedCountProperty(): int
    {
        return count($this->selectedIds);
    }
}
