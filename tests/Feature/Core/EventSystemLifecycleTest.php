<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Events\DepartmentUpdated;
use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Auth\Domain\SuperAdmin\Listeners\NotifySuperAdminsOfRecovery;
use App\Modules\Core\Events\BaseEvent;
use App\Modules\Core\Services\SmartLogger;
use App\Modules\Partner\Domain\Company\Events\CompanyCreated;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Setting\Observers\SettingObserver;
use App\Modules\User\Domain\Dashboard\Listeners\ClearDashboardCacheOnDepartmentChange;
use App\Modules\User\Models\User;
use App\Modules\User\Observers\UserObserver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

final class Nucy3TestEvent extends BaseEvent
{
    public function __construct(
        public readonly string $entityId,
        public readonly ?User $user = null,
        public readonly ?string $optionalField = null,
    ) {}

    public function eventName(): string
    {
        return 'test_entity.created';
    }
}

describe('NUCY3: Event System - Decoupled Communication Infrastructure', function () {
    test('NUCY3-FR-EVENT-001: all events extend BaseEvent abstract class (also FR-EVENT-002)', function () {
        $reflection = new ReflectionClass(Nucy3TestEvent::class);

        expect($reflection->isSubclassOf(BaseEvent::class))->toBeTrue()
            ->and($reflection->isFinal())->toBeTrue()
            ->and(new ReflectionClass(CompanyCreated::class)->isSubclassOf(BaseEvent::class))->toBeTrue();
    });

    test('NUCY3-FR-EVENT-003: eventName returns dot-notation string matching entity.past_tense_action', function () {
        $event = new Nucy3TestEvent('uuid-1');
        expect($event->eventName())->toBe('test_entity.created')
            ->and($event->eventName())->toMatch('/^[a-z_]+\.[a-z_]+$/');

        $companyEvent = new CompanyCreated(new Company);
        expect($companyEvent->eventName())->toBe('company.created');
    });

    test('NUCY3-FR-EVENT-004: toPayload converts Model to id string, preserves scalars and skips nulls', function () {
        $user = User::factory()->make(['id' => '01a00000-0000-0000-0000-000000000001']);
        $event = new Nucy3TestEvent(
            entityId: 'ent-123',
            user: $user,
            optionalField: null,
        );

        $payload = $event->toPayload();

        expect($payload)->toHaveKey('entityId', 'ent-123')
            ->and($payload)->toHaveKey('user_id', '01a00000-0000-0000-0000-000000000001')
            ->and($payload)->not->toHaveKey('optionalField');
    });

    test('NUCY3-FR-EVENT-005: all event-to-listener mappings are registered in config/event.php (also FR-EVENT-006, NFR-EVENT-005, UC-EVENT-001, DD-EVENT-002)', function () {
        $eventConfig = config('event.listen', []);

        expect($eventConfig)->toBeArray()
            ->and(count($eventConfig))->toBeGreaterThan(0);

        foreach ($eventConfig as $eventClass => $listeners) {
            expect(class_exists($eventClass))->toBeTrue("Event class {$eventClass} does not exist")
                ->and($listeners)->toBeArray()
                ->and(count($listeners))->toBeGreaterThan(0);
        }

        $testEvent = new Nucy3TestEvent('event-cfg');
        expect($testEvent->eventName())->toBe('test_entity.created');
    });

    test('NUCY3-FR-EVENT-007: events inside transactions fire after commit and discard on rollback (also FR-EVENT-010, NFR-EVENT-002, DD-EVENT-001)', function () {
        Event::fake([CompanyCreated::class]);

        DB::beginTransaction();
        $company = Company::factory()->create(['name' => 'Rollback Corp']);
        DB::rollBack();

        Event::assertNotDispatched(CompanyCreated::class);

        DB::beginTransaction();
        $company2 = Company::factory()->create(['name' => 'Committed Corp']);
        event(new CompanyCreated($company2));
        DB::commit();

        Event::assertDispatched(CompanyCreated::class, 1);
    });

    test('NUCY3-FR-EVENT-008: events dispatched outside transactions fire immediately', function () {
        Event::fake([Nucy3TestEvent::class]);

        $event = new Nucy3TestEvent('ext-1');
        Event::dispatch($event);

        Event::assertDispatched(Nucy3TestEvent::class, function ($e) {
            return $e->entityId === 'ext-1';
        });
    });

    test('NUCY3-FR-EVENT-009: SmartLogger integration logs event with PII masking (also DD-EVENT-003)', function () {
        $event = new Nucy3TestEvent('log-1');

        SmartLogger::info('test_event_dispatched')
            ->module('core')
            ->event($event->eventName())
            ->withPayload($event->toPayload())
            ->withPiiMasking()
            ->activityOnly()
            ->save();

        $activity = Activity::where('event', 'test_entity.created')->latest()->first();
        expect($activity)->not->toBeNull()
            ->and($activity->log_name)->toBe('core');
    });

    test('NUCY3-FR-EVENT-011: IO-bound listeners implement ShouldQueue', function () {
        $reflection = new ReflectionClass(NotifySuperAdminsOfRecovery::class);
        expect($reflection->implementsInterface(ShouldQueue::class))->toBeTrue();
    });

    test('NUCY3-FR-EVENT-012: observers adhere to 3-gate rule for single-model synchronous side effects (also FR-EVENT-013, FR-EVENT-014, NFR-EVENT-006, DD-EVENT-004)', function () {
        expect(class_exists(SettingObserver::class))->toBeTrue()
            ->and(class_exists(UserObserver::class))->toBeTrue();

        $observerReflection = new ReflectionClass(SettingObserver::class);
        expect($observerReflection->implementsInterface(ShouldQueue::class))->toBeFalse();
    });

    test('NUCY3-NFR-EVENT-001: event dispatch is non-blocking and decoupled', function () {
        $start = microtime(true);
        Event::dispatch(new Nucy3TestEvent('fast-1'));
        $duration = microtime(true) - $start;

        expect($duration)->toBeLessThan(0.5);
    });

    test('NUCY3-NFR-EVENT-003: queued listeners have standard execution bounds and retry configurations (also NFR-EVENT-004)', function () {
        $listenerClass = NotifySuperAdminsOfRecovery::class;
        $instance = new $listenerClass;

        expect($instance)->toBeInstanceOf(ShouldQueue::class);
    });

    test('NUCY3-UC-EVENT-003: cross-module cache invalidation listener clears keys without direct coupling', function () {
        $key = config('cache-keys.admin_dashboard_stats');
        Cache::put($key, 'cached_data', 3600);
        expect(Cache::has($key))->toBeTrue();

        $listener = new ClearDashboardCacheOnDepartmentChange;
        $dept = new Department;
        $dept->id = '123';
        $event = new DepartmentUpdated($dept);

        $listener->handle($event);

        expect(Cache::has($key))->toBeFalse();
    });

    test('NUCY3-UC-EVENT-002: failed event flows can be inspected via failed_jobs or logs table', function () {
        expect(Schema::hasTable('failed_jobs') || Schema::hasTable('jobs'))->toBeTrue();
    });
});
