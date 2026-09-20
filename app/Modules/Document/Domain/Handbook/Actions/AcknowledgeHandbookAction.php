<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Handbook\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Services\SmartLogger;
use App\Modules\Document\Models\Document;
use App\Modules\User\Models\User;
use Spatie\Activitylog\Models\Activity;

final class AcknowledgeHandbookAction extends BaseCommandAction
{
    public function execute(Document $handbook, User $user): void
    {
        $lastAck = Activity::causedBy($user)
            ->forEvent('acknowledged')
            ->where('subject_id', $handbook->id)
            ->where('subject_type', Document::class)
            ->latest()
            ->first();

        if (! $handbook->asHandbook()->isNewerThan($lastAck)) {
            throw new RejectedException(__('handbook.already_acknowledged'));
        }

        $this->transaction(function () use ($handbook, $user) {
            SmartLogger::info('handbook_acknowledged')
                ->for($user)
                ->about($handbook)
                ->module('Document')
                ->event('acknowledged')
                ->withPayload([
                    'user_id' => $user->id,
                    'version' => $handbook->version,
                    'ip' => request()->ip(),
                ])
                ->withPiiMasking()
                ->activityOnly()
                ->save();
        });
    }
}
