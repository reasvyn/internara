<?php

declare(strict_types=1);

namespace App\Document\Handbook\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Document\Enums\DocumentCategory;
use App\Document\Handbook\Data\HandbookData;
use App\Document\Handbook\Events\HandbookCreated;
use App\Document\Models\Document;
use Illuminate\Support\Str;

final class CreateHandbookAction extends BaseCommandAction
{
    public function execute(HandbookData $data): Document
    {
        return $this->transaction(function () use ($data) {
            $handbook = Document::create([
                'type' => DocumentCategory::HANDBOOK->value,
                'title' => $data->title,
                'slug' => Str::slug($data->title).'-'.uniqid(),
                'version' => 1,
                'is_active' => $data->isActive,
                'metadata' => [
                    'target_audience' => $data->audience->value,
                    'description' => $data->description,
                ],
                'created_by' => auth()->id(),
            ]);

            if ($data->file) {
                $handbook->addMedia($data->file)->toMediaCollection('handbook_file');
            }

            $handbook->load('media');

            $this->log('handbook_created', $handbook, [
                'title' => $handbook->title,
                'audience' => $data->audience->value,
            ]);

            event(new HandbookCreated($handbook));

            return $handbook;
        });
    }
}
