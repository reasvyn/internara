<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Guidance\Models\Handbook;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Creates a new handbook version.
 *
 * S1 - Secure: Only authorized roles can create guidance documents.
 */
final class CreateHandbookAction extends BaseAction
{
    /**
     * @param array<int, UploadedFile> $files
     */
    public function execute(User $user, array $data, array $files = []): Handbook
    {
        return $this->transaction(function () use ($user, $data, $files) {
            $handbook = Handbook::create([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'content' => $data['content'] ?? '',
                'version' => $data['version'] ?? '1.0',
                'is_active' => $data['is_active'] ?? false,
                'target_audience' => $data['target_audience'] ?? 'all',
                'published_at' => $data['is_active'] ? now() : null,
                'created_by' => $user->id,
            ]);

            foreach ($files as $file) {
                $handbook->addMedia($file)->toMediaCollection('files');
            }

            $this->log('handbook_created', $handbook, ['title' => $handbook->title, 'version' => $handbook->version]);

            return $handbook;
        });
    }
}
