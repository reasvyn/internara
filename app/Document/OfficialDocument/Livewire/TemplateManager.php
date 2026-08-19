<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Livewire;

use App\Document\Enums\DocumentCategory;
use App\Document\Models\Document;
use App\Document\OfficialDocument\Actions\SaveDocumentTemplateAction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class TemplateManager extends Component
{
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
            ['key' => 'title', 'label' => 'Name', 'sortable' => true],
            ['key' => 'type', 'label' => 'Category'],
            ['key' => 'is_active', 'label' => 'Active'],
            ['key' => 'created_at', 'label' => 'Created', 'sortable' => true],
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

        flash()->success(__('document.template_saved'));
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
