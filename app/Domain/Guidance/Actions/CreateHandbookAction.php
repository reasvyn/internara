<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Guidance\Models\Handbook;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates a new handbook version.
 *
 * S1 - Secure: Only authorized roles can create guidance documents.
 */
class CreateHandbookAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): Handbook
    {
        return DB::transaction(function () use ($user, $data) {
            $handbook = Handbook::create([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'content' => $data['content'] ?? '',
                'version' => $data['version'] ?? '1.0',
                'is_active' => $data['is_active'] ?? false,
                'published_at' => $data['is_active'] ? now() : null,
                'created_by' => $user->id,
            ]);

            $this->logAudit->execute(
                action: 'handbook_created',
                subjectType: Handbook::class,
                subjectId: $handbook->id,
                payload: ['title' => $handbook->title, 'version' => $handbook->version],
                module: 'Guidance',
            );

            return $handbook;
        });
    }
}
