<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Guidance\Models\Handbook;
use Illuminate\Http\UploadedFile;

final class UpdateHandbookAction extends BaseAction
{
    /**
     * @param array<int, UploadedFile> $files
     * @param array<int, string> $removeFileIds Media UUIDs to remove
     */
    public function execute(Handbook $handbook, array $data, array $files = [], array $removeFileIds = []): Handbook
    {
        return $this->transaction(function () use ($handbook, $data, $files, $removeFileIds) {
            $handbook->update([
                'title' => $data['title'] ?? $handbook->title,
                'content' => $data['content'] ?? $handbook->content,
                'version' => $data['version'] ?? $handbook->version,
                'is_active' => $data['is_active'] ?? $handbook->is_active,
                'target_audience' => $data['target_audience'] ?? $handbook->target_audience,
                'published_at' => isset($data['is_active']) && $data['is_active'] && ! $handbook->published_at
                    ? now()
                    : $handbook->published_at,
            ]);

            foreach ($removeFileIds as $mediaId) {
                $handbook->media()->where('uuid', $mediaId)->each(fn ($media) => $media->delete());
            }

            foreach ($files as $file) {
                $handbook->addMedia($file)->toMediaCollection('files');
            }

            $this->log('handbook_updated', $handbook, ['title' => $handbook->title, 'version' => $handbook->version]);

            return $handbook;
        });
    }
}
