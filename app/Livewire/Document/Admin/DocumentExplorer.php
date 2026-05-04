<?php

declare(strict_types=1);

namespace App\Livewire\Document\Admin;

use App\Domain\Document\Models\OfficialDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentExplorer extends Component
{
    use WithPagination;

    public string $search = '';

    public function headers(): array
    {
        return [
            ['key' => 'title', 'label' => 'Title', 'sortable' => true],
            ['key' => 'document_number', 'label' => 'Doc #'],
            ['key' => 'documentable_type', 'label' => 'Owner'],
            ['key' => 'issued_at', 'label' => 'Issued', 'sortable' => true],
        ];
    }

    public function documents(): LengthAwarePaginator
    {
        return OfficialDocument::query()
            ->when(
                $this->search,
                fn (Builder $q) => $q
                    ->where('title', 'like', "%{$this->search}%")
                    ->orWhere('document_number', 'like', "%{$this->search}%"),
            )
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.document.document-explorer', [
            'documents' => $this->documents(),
            'headers' => $this->headers(),
        ]);
    }
}
