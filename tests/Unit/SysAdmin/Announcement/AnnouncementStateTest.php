<?php

declare(strict_types=1);

use App\Modules\SysAdmin\Domain\Announcement\Entities\AnnouncementState;
use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class AnnouncementStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('3S55V: announcement state', function (): void {
    test('3S55V-FR-ANN-003: fromModel bridges stored status strings without persisting', function (): void {
        $model = new AnnouncementStateModelDouble(['status' => 'scheduled', 'scheduled_at' => Carbon::now()->subMinute()]);

        $state = AnnouncementState::fromModel($model);

        expect($state->isScheduled())->toBeTrue();
        expect($state->isPendingPublish(Carbon::now()))->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('3S55V-FR-ANN-003: fromModel accepts an already-cast status enum', function (): void {
        $model = new AnnouncementStateModelDouble(['status' => AnnouncementStatus::PUBLISHED, 'scheduled_at' => null]);

        $state = AnnouncementState::fromModel($model);

        expect($state->isPublished())->toBeTrue();
        expect($state->isDraft())->toBeFalse();
        expect($state->isPendingPublish(Carbon::now()))->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('3S55V-FR-ANN-003: status predicates answer exactly one state', function (): void {
        $draft = AnnouncementState::fromArray(['status' => AnnouncementStatus::DRAFT, 'scheduledAt' => null]);
        $scheduled = AnnouncementState::fromArray(['status' => AnnouncementStatus::SCHEDULED, 'scheduledAt' => Carbon::now()->addHour()]);
        $published = AnnouncementState::fromArray(['status' => AnnouncementStatus::PUBLISHED, 'scheduledAt' => null]);

        expect($draft->isDraft())->toBeTrue();
        expect($draft->isScheduled())->toBeFalse();
        expect($draft->isPublished())->toBeFalse();
        expect($scheduled->isScheduled())->toBeTrue();
        expect($scheduled->isPublished())->toBeFalse();
        expect($published->isPublished())->toBeTrue();
        expect($published->isDraft())->toBeFalse();
    });

    test('3S55V-FR-ANN-019: isPendingPublish trips only for due scheduled rows', function (): void {
        $now = Carbon::parse('2026-05-01 12:00:00');
        $due = AnnouncementState::fromArray(['status' => AnnouncementStatus::SCHEDULED, 'scheduledAt' => Carbon::parse('2026-05-01 11:59:00')]);
        $future = AnnouncementState::fromArray(['status' => AnnouncementStatus::SCHEDULED, 'scheduledAt' => Carbon::parse('2026-05-01 13:00:00')]);
        $dateless = AnnouncementState::fromArray(['status' => AnnouncementStatus::SCHEDULED, 'scheduledAt' => null]);
        $draft = AnnouncementState::fromArray(['status' => AnnouncementStatus::DRAFT, 'scheduledAt' => Carbon::parse('2026-05-01 11:00:00')]);

        expect($due->isPendingPublish($now))->toBeTrue();
        expect($future->isPendingPublish($now))->toBeFalse();
        expect($dateless->isPendingPublish($now))->toBeFalse();
        expect($draft->isPendingPublish($now))->toBeFalse();
    });

    test('3S55V-FR-ANN-003: fromArray rejects a missing status', function (): void {
        expect(fn (): AnnouncementState => AnnouncementState::fromArray(['scheduledAt' => null]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });

    test('3S55V-FR-ANN-003: toArray, equals, and with round-trip a dateless snapshot', function (): void {
        $state = AnnouncementState::fromArray(['status' => AnnouncementStatus::DRAFT, 'scheduledAt' => null]);

        expect($state->toArray())->toBe(['status' => AnnouncementStatus::DRAFT, 'scheduledAt' => null]);
        expect($state->equals(AnnouncementState::fromArray(['status' => AnnouncementStatus::DRAFT, 'scheduledAt' => null])))->toBeTrue();

        $scheduled = $state->with('status', AnnouncementStatus::SCHEDULED);

        expect($scheduled->isScheduled())->toBeTrue();
        expect($state->isDraft())->toBeTrue();
    });
});
