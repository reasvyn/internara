<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\OfficialDocument\Livewire;

use App\Modules\Document\Domain\OfficialDocument\Actions\SaveDocumentTemplateAction;
use App\Modules\Document\Enums\DocumentCategory;
use App\Modules\Document\Models\Document;
use App\Modules\UI\Livewire\BaseRecordManager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class TemplateManager extends BaseRecordManager
{
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
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Document::query()->where('type', '!=', 'report');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('title', 'like', "%{$this->search}%");
    }

    public function categories(): array
    {
        return array_map(
            fn (DocumentCategory $category): array => [
                'id' => $category->value,
                'name' => $category->label(),
            ],
            array_filter(
                DocumentCategory::cases(),
                fn (DocumentCategory $c): bool => $c !== DocumentCategory::REPORT
            )
        );
    }

    public function createTemplate(): void
    {
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

    public function editTemplate(string $id): void
    {
        $doc = Document::findOrFail($id);
        $this->templateData = [
            'id' => $doc->id,
            'title' => $doc->title,
            'type' => $doc->type,
            'description' => $doc->metadata['description'] ?? '',
            'content' => $doc->content,
            'is_active' => $doc->is_active,
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

        $this->templateModal = false;
        $this->toast()->success(__('document.template_saved'))->send();
    }

    public function render(): View
    {
        return view('document.official-document.template-manager', [
            'templates' => $this->rows(),
            'headers' => $this->headers(),
        ]);
    }
}
