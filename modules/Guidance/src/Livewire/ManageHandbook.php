<?php

declare(strict_types=1);

namespace Modules\Guidance\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Guidance\Models\Handbook;
use Modules\Guidance\Services\Contracts\HandbookService;

/**
 * Class ManageHandbook
 *
 * Provides the administrative interface for managing instructional handbooks.
 */
class ManageHandbook extends Component
{
    use WithPagination;

    /**
     * Search query for filtering handbooks.
     */
    #[Url(history: true)]
    public string $search = '';

    /**
     * Selected handbook ID for editing.
     */
    public ?string $selectedHandbookId = null;

    /**
     * Whether the handbook form modal is open.
     */
    public bool $showForm = false;

    /**
     * Reset pagination when searching.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Open the form to create a new handbook.
     */
    public function create(): void
    {
        $this->authorize('create', Handbook::class);

        $this->selectedHandbookId = null;
        $this->showForm = true;
    }

    /**
     * Open the form to edit an existing handbook.
     */
    public function edit(string $id): void
    {
        $this->authorize('update', Handbook::class);

        $this->selectedHandbookId = $id;
        $this->showForm = true;
    }

    /**
     * Delete a handbook.
     */
    public function delete(string $id, HandbookService $service): void
    {
        $this->authorize('delete', Handbook::class);

        if ($service->delete($id)) {
            flash()->success(__('guidance::messages.handbook_deleted'));
        }
    }

    /**
     * Handle handbook saved event.
     */
    public function handbookSaved(): void
    {
        $this->showForm = false;
        flash()->success(__('guidance::messages.handbook_saved'));
    }

    /**
     * Render the component view.
     */
    public function render(HandbookService $service): View
    {
        $this->authorize('viewAny', Handbook::class);

        $headers = [
            ['key' => 'title', 'label' => __('guidance::ui.handbook_title')],
            ['key' => 'version', 'label' => __('guidance::ui.version_label')],
            ['key' => 'is_mandatory', 'label' => __('guidance::ui.mandatory')],
            ['key' => 'is_active', 'label' => __('guidance::ui.status')],
        ];

        $handbooks = $service->paginate(filters: ['search' => $this->search], perPage: 10);

        return view('guidance::livewire.manage-handbook', [
            'handbooks' => $handbooks,
            'headers' => $headers,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('guidance::ui.manage_title').
                ' | '.
                setting('brand_name', setting('app_name')),
        ]);
    }
}
