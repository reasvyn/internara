<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Document;

use App\Models\DocumentTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class TemplateManager extends Component
{
    use Toast, WithPagination;

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
        return DocumentTemplate::query()
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

    public function editTemplate(DocumentTemplate $template): void
    {
        $this->resetErrorBag();
        $this->templateData = $template->toArray();
        $this->templateModal = true;
    }

    public function saveTemplate(): void
    {
        $this->validate([
            'templateData.name' => 'required|string|max:255',
            'templateData.category' => 'required|string',
            'templateData.content' => 'required|string',
        ]);

        DocumentTemplate::updateOrCreate(
            ['id' => $this->templateData['id']],
            array_merge($this->templateData, [
                'slug' => str($this->templateData['name'])->slug()->toString(),
            ])
        );

        $this->success('Template saved successfully.');
        $this->templateModal = false;
    }

    public function render()
    {
        return view('livewire.admin.document.template-manager', [
            'templates' => $this->templates(),
            'headers' => $this->headers(),
        ]);
    }
}
