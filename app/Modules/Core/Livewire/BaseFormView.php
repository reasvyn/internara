<?php

declare(strict_types=1);

namespace App\Modules\Core\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Base class for full-page or multi-tab form components.
 *
 * Unlike BaseRecordEntry (modal-based CRUD), BaseFormView is for forms that
 * occupy a full page or are organized into tabs/sections. The form is always
 * visible — there is no show/hide modal toggle.
 *
 * Provides:
 * - Dirty state tracking (form modified indicator)
 * - RejectedException handling pattern (handleSave)
 * - Flash message helpers
 *
 * Does NOT include WithFileUploads — add it in subclasses that need it.
 *
 * Examples: ProfileEditor, SystemSetting, SchoolEditor
 */
abstract class BaseFormView extends Component
{
    use Interactions;

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
            $this->toast()->error($e->getMessage())->send();
        } catch (\Throwable $e) {
            $this->toast()->error(__('common.actions.error_occurred'))->send();
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
