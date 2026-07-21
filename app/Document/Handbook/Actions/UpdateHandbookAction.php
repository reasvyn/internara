<?php

declare(strict_types=1);

namespace App\Document\Handbook\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Document\Handbook\Data\HandbookData;
use App\Document\Handbook\Events\HandbookUpdated;
use App\Document\Models\Document;

final class UpdateHandbookAction extends BaseCommandAction
{
    public function execute(Document $handbook, HandbookData $data): Document
    {
        return $this->transaction(function () use ($handbook, $data) {
            $metadata = $handbook->metadata ?? [];
            $metadata['target_audience'] = $data->audience->value;
            $metadata['description'] = $data->description;

            $updateData = [
                'title' => $data->title,
                'is_active' => $data->isActive,
                'metadata' => $metadata,
            ];

            if ($data->file) {
                $updateData['version'] = ($handbook->version ?? 1) + 1;
            }

            $handbook->update($updateData);

            if ($data->file) {
                $handbook->clearMediaCollection('handbook_file');
                $handbook->addMedia($data->file)->toMediaCollection('handbook_file');
            }

            $handbook->load('media');

            $this->log('handbook_updated', $handbook, [
                'title' => $handbook->title,
                'version' => $handbook->version,
            ]);

            event(new HandbookUpdated($handbook));

            return $handbook;
        });
    }
}
