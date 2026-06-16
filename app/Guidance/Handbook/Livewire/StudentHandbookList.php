<?php

declare(strict_types=1);

namespace App\Guidance\Handbook\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Document\Enums\DocumentCategory;
use App\Document\Models\Document;
use App\Guidance\Handbook\Actions\AcknowledgeHandbookAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentHandbookList extends Component
{
    public function acknowledge(string $id, AcknowledgeHandbookAction $action): void
    {
        $handbook = Document::ofType(DocumentCategory::HANDBOOK->value)->findOrFail($id);

        if (! $handbook->asHandbook()->isTargetedAt(auth()->user())) {
            abort(403);
        }

        try {
            $action->execute($handbook, auth()->user());
            flash()->success(__('guidance.acknowledged'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function download(string $id): StreamedResponse
    {
        $handbook = Document::ofType(DocumentCategory::HANDBOOK->value)->findOrFail($id);
        $entity = $handbook->asHandbook();

        if (! $entity->isTargetedAt(auth()->user())) {
            abort(403);
        }

        if (! $entity->isAvailable()) {
            abort(404);
        }

        $media = $handbook->getFirstMedia('handbook_file');

        abort_unless($media, 404);

        return $media->toResponse(request());
    }

    #[Computed]
    public function handbooks(): Collection
    {
        $user = auth()->user();

        $all = Document::ofType(DocumentCategory::HANDBOOK->value)
            ->where('is_active', true)
            ->get();

        return $all->filter(fn ($doc) => $doc->asHandbook()->isTargetedAt($user));
    }

    #[Computed]
    public function acknowledgments(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        $acknowledgments = Activity::causedBy($user)
            ->inLog('document')
            ->forEvent('acknowledged')
            ->where('subject_type', Document::class)
            ->latest()
            ->get()
            ->keyBy('subject_id');

        return $acknowledgments;
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('guidance.handbook.student-handbook-list');
    }
}
