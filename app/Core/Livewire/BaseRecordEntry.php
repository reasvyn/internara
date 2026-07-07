<?php

declare(strict_types=1);

namespace App\Core\Livewire;

use App\Core\Exceptions\RejectedException;
use Livewire\Component;

/**
 * Base class for user-facing CRUD components with form modals.
 *
 * Unlike BaseRecordManager (sysadmin full CRUD with table management),
 * BaseRecordEntry is for non-admin components where users create or edit
 * individual records (e.g., logbook entries, absence requests, submissions).
 *
 * Provides:
 * - Modal state management (show/hide, reset on open)
 * - Form validation and error handling
 * - RejectedException handling pattern (handleError)
 * - Flash message helpers
 *
 * Does NOT include WithFileUploads — add it in subclasses that need it.
 *
 * Examples: LogbookEntry
 */
abstract class BaseRecordEntry extends Component
{
    /** @var bool Whether the form modal is visible */
    public bool $showModal = false;

    /** @var string|null The ID of the record being edited (null for create) */
    public ?string $editingId = null;

    /**
     * Open the form modal for creating a new record.
     * Override to set default values.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    /**
     * Open the form modal for editing an existing record.
     * Override to populate form fields from the model.
     */
    abstract public function edit(string $id): void;

    /**
     * Close the form modal and reset form state.
     */
    public function cancel(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset form fields to default values.
     * Override to reset custom properties.
     */
    protected function resetForm(): void
    {
        $this->editingId = null;
    }

    /**
     * Handle RejectedException from Action calls with flash message.
     */
    protected function handleError(callable $callback): void
    {
        try {
            $callback();
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        } catch (\Throwable $e) {
            flash()->error(__('common.actions.error_occurred'));
        }
    }
}
