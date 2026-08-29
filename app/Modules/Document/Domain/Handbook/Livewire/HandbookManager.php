<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Handbook\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\Document\Enums\DocumentCategory;
use App\Modules\Document\Domain\Handbook\Actions\CreateHandbookAction;
use App\Modules\Document\Domain\Handbook\Actions\DeleteHandbookAction;
use App\Modules\Document\Domain\Handbook\Actions\UpdateHandbookAction;
use App\Modules\Document\Domain\Handbook\Data\HandbookData;
use App\Modules\Document\Domain\Handbook\Enums\HandbookAudience;
use App\Modules\Document\Domain\Handbook\Livewire\Forms\HandbookForm;
use App\Modules\Document\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

class HandbookManager extends BaseRecordManager
{
    use Interactions;
    use WithFileUploads;

    public bool $showModal = false;

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
            ['index' => 'title', 'label' => __('handbook.title_field'), 'sortable' => true],
            ['index' => 'audience', 'label' => __('handbook.target_audience')],
            ['index' => 'version', 'label' => __('handbook.version_field'), 'sortable' => true],
            ['index' => 'is_active', 'label' => __('handbook.status')],
            ['index' => 'created_at', 'label' => __('common.created_at'), 'sortable' => true],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
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
            $this->toast()->success(__('handbook.updated'))->send();
        } else {
            $this->authorize('create', Document::class);
            $create->execute($data);
            $this->toast()->success(__('handbook.created'))->send();
        }

        $this->showModal = false;
        $this->uploadFile = null;
    }

    public function askDelete(string $id): void
    {
        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
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
            $this->toast()->success(__('handbook.deleted'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
        $this->confirmTarget = null;
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('document.handbook.handbook-manager');
    }
}
