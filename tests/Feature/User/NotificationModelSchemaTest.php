<?php

declare(strict_types=1);

use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\User\Domain\Notify\Actions\SendNotificationAction;
use App\Modules\User\Domain\Notify\Models\Notification;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

describe('TXR2H: notification persistence shape and storage bounds', function (): void {
    test('TXR2H-FR-NOTIF-006: model fillable, casts, and owner relation stay a thin persistence adapter', function (): void {
        $notification = new Notification;

        expect($notification->getFillable())->toBe([
            'user_id', 'type', 'title', 'message', 'data', 'link', 'is_read', 'read_at',
        ]);

        $casts = $notification->getCasts();
        expect($casts['data'])->toBe('array')
            ->and($casts['is_read'])->toBe('boolean')
            ->and($casts['read_at'])->toBe('datetime');

        // The user() relation previously resolved User against the model's own
        // namespace (missing import) and threw; assert it now returns a working BelongsTo.
        $relation = $notification->user();

        expect($relation)->toBeInstanceOf(BelongsTo::class);

        $owner = User::factory()->create();
        $row = Notification::factory()->create(['user_id' => $owner->id]);

        expect($row->user->id)->toBe($owner->id);
    });

    test('TXR2H-FR-NOTIF-007: notifications table carries the owner/read composite index and cascades on user delete', function (): void {
        $indexes = Schema::getIndexes('notify');
        $hasOwnerReadIndex = collect($indexes)->contains(
            fn ($index) => empty($index['unique'])
                && collect($index['columns'] ?? [])->sort()->values()->all() === ['is_read', 'user_id'],
        );
        expect($hasOwnerReadIndex)->toBeTrue('composite index on [user_id, is_read] must exist');

        $foreignKeys = Schema::getForeignKeys('notify');
        $ownerKey = collect($foreignKeys)->firstWhere('columns', ['user_id']);
        expect($ownerKey)->not->toBeNull();
        expect($ownerKey['foreign_table'] ?? null)->toBe('users');
        expect(strtolower($ownerKey['on_delete'] ?? ''))->toBe('cascade');

        $user = User::factory()->create();
        Notification::factory()->create(['user_id' => $user->id]);
        expect(Notification::where('user_id', $user->id)->exists())->toBeTrue();

        $user->delete();

        expect(Notification::where('user_id', $user->id)->exists())->toBeFalse();
    });

    test('TXR2H-NFR-NOTIF-004: category key accepts 50 chars and rejects anything longer', function (): void {
        $user = User::factory()->create();
        $action = app(SendNotificationAction::class);

        $maxType = str_repeat('k', 50);
        $row = $action->execute(new NotificationData(
            userId: $user->id, type: $maxType, title: 'Batas atas kategori',
        ));
        expect($row->type)->toBe($maxType);

        expect(fn () => $action->execute(new NotificationData(
            userId: $user->id, type: str_repeat('k', 51), title: 'Kelebihan satu karakter',
        )))->toThrow(ValidationException::class);

        expect(Notification::where('user_id', $user->id)->count())->toBe(1);
    });

    test('TXR2H-NFR-NOTIF-008: every notification-subsystem file declares strict types', function (): void {
        $roots = [
            'app/Modules/User/Domain/Notify',
            'app/Modules/Core/Channels',
            'app/Modules/Core/Contracts',
            'app/Modules/Assignment/Notify',
            'app/Modules/Assignment/Domain/Submission/Notify',
            'app/Modules/Program/Notify',
            'app/Modules/SysAdmin/Domain/Announcement/Notify',
            'app/Modules/User/Domain/AccountStatus/Notify',
            'app/Modules/Incident/Domain/IncidentReport/Notify',
            'app/Modules/Auth/Notify',
        ];
        $extraFiles = [
            'app/Modules/Assignment/Listeners/NotifyOnAssignmentPublished.php',
            'app/Modules/Program/Domain/Internship/Listeners/NotifyAdminsInternshipCreated.php',
            'app/Modules/Auth/Domain/Login/Listeners/SendRoleWelcomeNotification.php',
        ];

        $files = [];
        foreach ($roots as $root) {
            foreach (glob(base_path($root.'/*.php')) ?: [] as $file) {
                $files[] = $file;
            }
            foreach (glob(base_path($root.'/**/*.php'), GLOB_BRACE) ?: [] as $file) {
                $files[] = $file;
            }
        }
        // Recurse one more level explicitly (Actions/, Livewire/, Events/, Listeners/, Models/, Policies/).
        foreach ($roots as $root) {
            foreach (glob(base_path($root.'/*/*.php')) ?: [] as $file) {
                $files[] = $file;
            }
        }
        foreach ($extraFiles as $extra) {
            $files[] = base_path($extra);
        }
        $files = array_values(array_unique(array_filter($files, 'is_file')));

        expect($files)->not->toBeEmpty();

        $violations = array_values(array_filter(
            $files,
            fn (string $file) => ! str_contains((string) file_get_contents($file), 'declare(strict_types=1);'),
        ));

        expect($violations)->toBe([], 'files missing declare(strict_types=1): '.implode(', ', $violations));
    });

    test('TXR2H-NFR-NOTIF-009: unread-count key lives in the registry with no ad-hoc copies in source', function (): void {
        expect(config('cache-keys.notification_unread'))->toBe('notification.unread:');

        $consumers = [
            'app/Modules/User/Domain/Notify/Livewire/NotificationBell.php',
            'app/Modules/User/Domain/Notify/Actions/MarkAllAsReadAction.php',
            'app/Modules/User/Domain/Notify/Actions/MarkBatchAsReadAction.php',
            'app/Modules/User/Domain/Notify/Actions/DeleteNotificationAction.php',
            'app/Modules/User/Domain/Notify/Listeners/ClearUnreadNotificationCache.php',
        ];

        foreach ($consumers as $relative) {
            $source = (string) file_get_contents(base_path($relative));
            expect(str_contains($source, "config('cache-keys.notification_unread')"))->toBeTrue($relative.' must resolve the registered key');
            expect(str_contains($source, "'notification.unread:'"))->toBeFalse($relative.' must not hardcode the key literal');
        }
    });
});
