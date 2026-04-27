<?php

declare(strict_types=1);

namespace Modules\UI\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Class RecordIndex
 *
 * Base abstract component for a standardized "Index" page that wraps a RecordManager.
 * Provides a consistent structure with statistics and a record management interface.
 */
abstract class RecordIndex extends Component
{
    /**
     * The Livewire component name for the RecordManager to embed.
     */
    protected string $managerComponent = '';

    /**
     * The translation key for the page title.
     */
    protected string $titleKey = '';

    /**
     * The translation key for the page subtitle.
     */
    protected string $subtitleKey = '';

    /**
     * Get summary metrics for the record type.
     * Override this method to provide actual statistics.
     * 
     * @return array<int, array{title: string, value: mixed, icon: string, variant: string}>
     */
    #[Computed]
    public function stats(): array
    {
        return [];
    }

    /**
     * Get the view name for the component.
     * Defaults to the shared UI record-index view.
     */
    protected function getView(): string
    {
        return 'ui::livewire.record-index';
    }

    /**
     * Render the component using the standardized dashboard layout.
     */
    public function render(): View
    {
        return view($this->getView(), [
            'managerComponent' => $this->managerComponent,
        ])->layout(
            'ui::components.layouts.dashboard',
            [
                'title' => ($this->titleKey ? __($this->titleKey) : '') . ' | ' . setting('brand_name', setting('app_name')),
                'context' => $this->titleKey,
            ],
        );
    }
}
