<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\OfficialDocument\Livewire;

use App\Modules\Document\Enums\DocumentCategory;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Domain\OfficialDocument\Actions\SaveDocumentTemplateAction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

class TemplateManager extends Component
{
    use Interactions;
    use WithPagination;

    public string $search = '';

    public bool $templateModal = false;

    public array $templateData = [
        'id' => null,
        'title' => '',
        'type' => 'application',
        'description' => '',
        'content' => '',
        'is_active' => true,
    ];

    public function headers(): array
    {
        return [
            ['index' => 'title', 'label' => 'Name', 'sortable' => true],
            ['index' => 'type', 'label' => 'Category'],
            ['index' => 'is_active', 'label' => 'Active'],
            ['index' => 'created_at', 'label' => 'Created', 'sortable' => true],
        ];
    }

    public function templates(): LengthAwarePaginator
    {
        return Document::query()
            ->where('type', '!=', 'report')
            ->when($this->search, fn (Builder $q) => $q->where('title', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);
    }

    public function categories(): array
    {
        return array_map(
            fn (DocumentCategory $category): array => [
                'id' => $category->value,
                'name' => $category->label(),
            ],
            DocumentCategory::cases(),
        );
    }

    public function createTemplate(): void
    {
        $this->resetErrorBag();
        $this->templateData = [
            'id' => null,
            'title' => '',
            'type' => 'application',
            'description' => '',
            'content' => '',
            'is_active' => true,
        ];
        $this->templateModal = true;
    }

    public function editTemplate(Document $template): void
    {
        $this->resetErrorBag();
        $this->templateData = [
            'id' => $template->id,
            'title' => $template->title,
            'type' => $template->type,
            'description' => $template->metadata['description'] ?? '',
            'content' => (string) $template->content,
            'is_active' => (bool) $template->is_active,
        ];
        $this->templateModal = true;
    }

    public function saveTemplate(SaveDocumentTemplateAction $action): void
    {
        $this->validate([
            'templateData.title' => 'required|string|max:255',
            'templateData.type' => 'required|string',
            'templateData.content' => 'required|string',
        ]);

        $action->execute($this->templateData);

        $this->toast()->success(__('document.template_saved'))->send();
        $this->templateModal = false;
    }

    public function render(): View
    {
        return view('document.official-document.template-manager', [
            'templates' => $this->templates(),
            'headers' => $this->headers(),
        ]);
    }
}
