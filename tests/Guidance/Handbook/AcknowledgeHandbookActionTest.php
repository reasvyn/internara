<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Document\Models\Document;
use App\Guidance\Handbook\Actions\AcknowledgeHandbookAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

describe('AcknowledgeHandbookAction', function () {
    test('acknowledges a handbook for the first time', function () {
        $user = User::factory()->create();
        $handbook = Document::factory()->create([
            'type' => 'handbook',
            'version' => 1,
        ]);

        app(AcknowledgeHandbookAction::class)->execute($handbook, $user);

        $activity = Activity::causedBy($user)
            ->forEvent('acknowledged')
            ->where('subject_id', $handbook->id)
            ->where('subject_type', Document::class)
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->properties['version'])->toBe(1);
    });

    test('throws when handbook version is not newer than last acknowledgment', function () {
        $user = User::factory()->create();
        $handbook = Document::factory()->create([
            'type' => 'handbook',
            'version' => 1,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($handbook)
            ->withProperties(['version' => 1])
            ->event('acknowledged')
            ->log('handbook_acknowledged');

        app(AcknowledgeHandbookAction::class)->execute($handbook, $user);
    })->throws(RejectedException::class);
});
