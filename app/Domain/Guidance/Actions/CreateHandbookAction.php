<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Guidance\Models\Handbook;
use App\Domain\User\Models\User;
use Illuminate\Support\Str;

/**
 * Creates a new handbook version.
 *
 * S1 - Secure: Only authorized roles can create guidance documents.
 */
class CreateHandbookAction extends BaseAction
{
    public function execute(User $user, array $data): Handbook
    {
        return $this->transaction(function () use ($user, $data) {
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

            $this->log('handbook_created', $handbook, ['title' => $handbook->title, 'version' => $handbook->version]);

            return $handbook;
        });
    }
}
