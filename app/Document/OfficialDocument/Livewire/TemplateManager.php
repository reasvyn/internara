<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Livewire;

use App\Document\Models\Document;
use App\Document\OfficialDocument\Actions\SaveDocumentTemplateAction;
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
        'name' => '',
        'category' => 'application',
        'description' => '',
        'content' => '',
        'is_active' => true,
    ];

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'is_active', 'label' => 'Active'],
            ['key' => 'created_at', 'label' => 'Created', 'sortable' => true],
        ];
    }

    public function templates(): LengthAwarePaginator
    {
        return Document::query()
            ->when($this->search, fn (Builder $q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);
    }

    public function createTemplate(): void
    {
        $this->resetErrorBag();
        $this->templateData = [
            'id' => null,
            'name' => '',
            'category' => 'application',
            'description' => '',
            'content' => '',
            'is_active' => true,
        ];
        $this->templateModal = true;
    }

    public function editTemplate(Document $template): void
    {
        $this->resetErrorBag();
        $this->templateData = $template->toArray();
        $this->templateModal = true;
    }

    public function saveTemplate(SaveDocumentTemplateAction $action): void
    {
        $this->validate([
            'templateData.name' => 'required|string|max:255',
            'templateData.category' => 'required|string',
            'templateData.content' => 'required|string',
        ]);

        $action->execute($this->templateData);

        flash()->success('Template saved successfully.');
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
