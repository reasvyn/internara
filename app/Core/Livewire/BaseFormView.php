<?php

declare(strict_types=1);

namespace App\Core\Livewire;

use App\Core\Exceptions\RejectedException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Base class for full-page or multi-tab form components.
 *
 * Unlike BaseRecordEntry (modal-based CRUD), BaseFormView is for forms that
 * occupy a full page or are organized into tabs/sections. The form is always
 * visible — there is no show/hide modal toggle.
 *
 * Provides:
 * - File upload support via WithFileUploads
 * - Dirty state tracking (form modified indicator)
 * - Confirmation prompt for unsaved changes
 * - RejectedException handling pattern
 * - Flash message helpers
 *
 * Examples: ProfileEditor, SystemSetting, SchoolEditor
 */
abstract class BaseFormView extends Component
{
    use WithFileUploads;

    /** @var bool Whether the form has unsaved changes */
    public bool $isDirty = false;

    /**
     * Handle form save with consistent try/catch pattern.
     * Call this from save methods instead of writing try/catch manually.
     */
    protected function handleSave(callable $callback): void
    {
        try {
            $callback();
            $this->isDirty = false;
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        } catch (\Throwable $e) {
            flash()->error(__('common.actions.error_occurred'));
        }
    }

    /**
     * Mark form as modified. Call from updated() hooks.
     */
    protected function markDirty(): void
    {
        $this->isDirty = true;
    }
}
