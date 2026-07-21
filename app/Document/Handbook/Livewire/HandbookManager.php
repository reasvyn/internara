<?php

declare(strict_types=1);

namespace App\Document\Handbook\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Document\Enums\DocumentCategory;
use App\Document\Handbook\Actions\CreateHandbookAction;
use App\Document\Handbook\Actions\DeleteHandbookAction;
use App\Document\Handbook\Actions\UpdateHandbookAction;
use App\Document\Handbook\Data\HandbookData;
use App\Document\Handbook\Enums\HandbookAudience;
use App\Document\Handbook\Livewire\Forms\HandbookForm;
use App\Document\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

class HandbookManager extends BaseRecordManager
{
    use WithFileUploads;

    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public $uploadFile = null;

    public HandbookForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', Document::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'title', 'label' => __('handbook.title_field'), 'sortable' => true],
            ['key' => 'audience', 'label' => __('handbook.target_audience')],
            ['key' => 'version', 'label' => __('handbook.version_field'), 'sortable' => true],
            ['key' => 'is_active', 'label' => __('handbook.status')],
            ['key' => 'created_at', 'label' => __('common.created_at'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Document::ofType(DocumentCategory::HANDBOOK->value);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('title', 'like', "%{$this->search}%");
        });
    }

    #[Computed]
    public function audienceOptions(): array
    {
        return collect(HandbookAudience::cases())
            ->map(fn ($a) => ['id' => $a->value, 'name' => $a->label()])
            ->toArray();
    }

    public function create(): void
    {
        $this->authorize('create', Document::class);
        $this->resetErrorBag();
        $this->form->reset();
        $this->form->id = null;
        $this->uploadFile = null;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $handbook = Document::ofType(DocumentCategory::HANDBOOK->value)->findOrFail($id);
        $this->authorize('update', $handbook);

        $this->resetErrorBag();
        $this->form->id = $handbook->id;
        $this->form->title = $handbook->title;
        $this->form->audience = $handbook->metadata['target_audience'] ?? 'all';
        $this->form->description = $handbook->metadata['description'] ?? null;
        $this->form->isActive = $handbook->is_active;
        $this->uploadFile = null;
        $this->showModal = true;
    }

    public function save(CreateHandbookAction $create, UpdateHandbookAction $update): void
    {
        $fileRules = $this->form->id ? 'nullable' : 'required';
        $this->validate([
            'form.title' => 'required|string|max:255',
            'form.audience' => 'required|string|in:all,student,teacher,supervisor',
            'uploadFile' => "{$fileRules}|file|mimes:pdf|max:10240",
        ]);

        $audience = HandbookAudience::tryFrom($this->form->audience) ?? HandbookAudience::ALL;

        $data = new HandbookData(
            title: $this->form->title,
            audience: $audience,
            description: $this->form->description,
            isActive: $this->form->isActive,
            file: $this->uploadFile,
        );

        if ($this->form->id) {
            $handbook = Document::ofType(DocumentCategory::HANDBOOK->value)->findOrFail($this->form->id);
            $this->authorize('update', $handbook);
            $update->execute($handbook, $data);
            flash()->success(__('handbook.updated'));
        } else {
            $this->authorize('create', Document::class);
            $create->execute($data);
            flash()->success(__('handbook.created'));
        }

        $this->showModal = false;
        $this->uploadFile = null;
    }

    public function askDelete(string $id): void
    {
        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->showConfirm = true;
    }

    public function confirmAction(DeleteHandbookAction $action): void
    {
        if ($this->confirmTarget === null) {
            return;
        }

        try {
            $handbook = Document::ofType(DocumentCategory::HANDBOOK->value)->findOrFail($this->confirmTarget);
            $this->authorize('delete', $handbook);
            $action->execute($handbook);
            flash()->success(__('handbook.deleted'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('document.handbook.handbook-manager');
    }
}
