<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Livewire\DepartmentManager;
use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Events\BaseEvent;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Livewire\BaseFormView;
use App\Modules\Core\Livewire\BaseRecordEntry;
use App\Modules\Core\Livewire\BaseRecordList;
use App\Modules\Core\Models\BaseAuthenticatable;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Core\Policies\BasePolicy;
use App\Modules\User\Domain\Notify\Actions\SendNotificationAction;
use App\Modules\User\Domain\Notify\Events\NotificationSent;
use App\Modules\User\Domain\Notify\Models\Notification;
use App\Modules\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

final class Se5q9FDeptCreated extends BaseEvent
{
    public function __construct(public Department $department) {}

    public function eventName(): string
    {
        return 'department.created';
    }
}

final class Se5q9FCreateDept extends BaseCommandAction
{
    public function execute(array $data): ActionResponse
    {
        $validated = $this->validate($data, ['name' => 'required|string|max:255']);

        return $this->transaction(function () use ($validated) {
            $department = Department::create(['name' => $validated['name']]);

            $this->dispatchEvent(new Se5q9FDeptCreated($department));
            $this->log('department.created', $department, ['name' => $department->name]);

            return $this->respond($department->toArray(), 'stored', true);
        });
    }
}

final class Se5q9FExplodingDept extends BaseCommandAction
{
    public function execute(array $data): ActionResponse
    {
        return $this->transaction(function () use ($data) {
            $department = Department::create(['name' => $data['name']]);

            $this->dispatchEvent(new Se5q9FDeptCreated($department));

            throw new RuntimeException('boom after write');
        });
    }
}

final class Se5q9FDeptStats extends BaseReadAction
{
    public function execute(): array
    {
        $key = $this->cacheKey('stats');

        return $this->remember($key, fn () => [
            'count' => Department::count(),
            'names' => Department::orderBy('name')->pluck('name')->all(),
        ]);
    }
}

final class Se5q9FScopeProbe extends BaseModel
{
    protected $table = 'users';
}

final class Se5q9FProbePolicy extends BasePolicy
{
    public function view(Model $user, Model $model): Response
    {
        return Response::deny('no entry');
    }

    public function pubIsAdmin(Model $user): bool
    {
        return $this->isAdmin($user);
    }

    public function pubCanManage(Model $user): bool
    {
        return $this->canManageAnyRole($user);
    }

    public function pubHasAny(Model $user, array $roles): bool
    {
        return $this->hasAnyOfRoles($user, $roles);
    }

    public function pubIsOwner(Model $user, Model $model, string $fk = 'user_id'): bool
    {
        return $this->isOwner($user, $fk === 'user_id' ? $model : $model, $fk);
    }

    public function pubIsRelated(Model $user, Model $model, string $relation, string $fk = 'id'): bool
    {
        return $this->isRelatedThrough($user, $model, $relation, $fk);
    }

    public function pubIsOwnerOrAdmin(Model $user, Model $model, string $fk = 'user_id'): bool
    {
        return $this->isOwnerOrAdmin($user, $model, $fk);
    }
}

final class Se5q9FEntry extends BaseRecordEntry
{
    public function edit(string $id): void
    {
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function save(callable $callback): void
    {
        $this->handleError($callback);
    }

    public function render(): string
    {
        return '<div>entry</div>';
    }
}

final class Se5q9FList extends BaseRecordList
{
    protected function query(): Builder
    {
        return Department::query();
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    public function render(): string
    {
        return '<div>list</div>';
    }
}

final class Se5q9FForm extends BaseFormView
{
    public function save(callable $callback): void
    {
        $this->handleSave($callback);
    }

    public function dirty(): void
    {
        $this->markDirty();
    }

    public function render(): string
    {
        return '<div>form</div>';
    }
}

final class Se5q9FQuotaAction extends BaseCommandAction
{
    public function execute(array $data): ActionResponse
    {
        $seats = (int) ($data['seats'] ?? 0);

        if ($seats < 1) {
            $this->fail('Placement quota is full', ['seats' => $seats]);
        }

        return $this->respond(['seats' => $seats], 'placed');
    }
}

describe('SE5Q9: command action transactions and events', function (): void {
    test('SE5Q9-FR-BASE-001: committed work persists and its event dispatches', function (): void {
        Event::fake([Se5q9FDeptCreated::class]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $response = (new Se5q9FCreateDept)->execute(['name' => 'RPL']);

        expect($response->success)->toBeTrue();
        expect(Department::where('name', 'RPL')->exists())->toBeTrue();
        Event::assertDispatched(Se5q9FDeptCreated::class);
    });

    test('SE5Q9-FR-BASE-006: a failed write rolls back and its queued event never fires', function (): void {
        Event::fake([Se5q9FDeptCreated::class]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        try {
            (new Se5q9FExplodingDept)->execute(['name' => 'Ghost']);
            expect(false)->toBeTrue('expected RuntimeException');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toBe('boom after write');
        }

        expect(Department::where('name', 'Ghost')->exists())->toBeFalse();
        Event::assertNotDispatched(Se5q9FDeptCreated::class);
    });

    test('SE5Q9-FR-BASE-007: successful mutations leave an audit trail', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        (new Se5q9FCreateDept)->execute(['name' => 'Audit Me']);

        $this->assertDatabaseHas('activity_log', ['description' => 'department.created']);
    });

    test('SE5Q9-FR-BASE-008: command returns ActionResponse while reads return plain data', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $created = (new Se5q9FCreateDept)->execute(['name' => 'RPL']);

        expect($created)->toBeInstanceOf(ActionResponse::class);
        expect($created->success)->toBeTrue();
        expect($created->data['name'])->toBe('RPL');
    });

    test('SE5Q9-FR-BASE-003: reads return data without mutating state or logging', function (): void {
        Cache::flush();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Department::factory()->create(['name' => 'RPL']);
        Department::factory()->create(['name' => 'TKJ']);

        $activitiesBefore = Activity::count();

        $result = (new Se5q9FDeptStats)->execute();

        expect($result['count'])->toBe(2);
        expect($result['names'])->toBe(['RPL', 'TKJ']);
        expect(Department::count())->toBe(2);
        expect(Activity::count())->toBe($activitiesBefore);
    });
});

describe('SE5Q9: models, scopes, and auth bridge', function (): void {
    test('SE5Q9-FR-BASE-009: BaseModel persists UUID v7 string keys', function (): void {
        $department = Department::factory()->create(['name' => 'RPL']);

        expect($department->getIncrementing())->toBeFalse();
        expect($department->getKeyType())->toBe('string');
        expect($department->id)->toBeString();
        expect((bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $department->id))->toBeTrue();

        expect($department->fresh()->name)->toBe('RPL');
        expect($department)->toBeInstanceOf(BaseModel::class);
    });

    test('SE5Q9-FR-BASE-010: User keeps UUID keys through BaseAuthenticatable', function (): void {
        $user = User::factory()->create();

        expect($user)->toBeInstanceOf(BaseAuthenticatable::class);
        expect($user)->toBeInstanceOf(Authenticatable::class);
        expect($user)->not->toBeInstanceOf(BaseModel::class);
        expect($user->getIncrementing())->toBeFalse();
        expect($user->getKeyType())->toBe('string');
        expect((bool) preg_match('/^[0-9a-f-]{36}$/i', $user->id))->toBeTrue();
    });

    test('SE5Q9-FR-BASE-014: common scopes filter, bound dates, and order users', function (): void {
        User::factory()->create(['name' => 'Active One', 'is_active' => true]);
        User::factory()->create(['name' => 'Active Two', 'is_active' => true]);
        User::factory()->create(['name' => 'Dormant', 'is_active' => false]);

        expect(Se5q9FScopeProbe::active()->count())->toBe(2);
        expect(Se5q9FScopeProbe::inactive()->count())->toBe(1);
        expect(Se5q9FScopeProbe::inactive()->first()->name)->toBe('Dormant');

        expect(Se5q9FScopeProbe::recent(1)->get())->toHaveCount(1);
        expect(Se5q9FScopeProbe::recent()->get()->first()->created_at)
            ->gte(Se5q9FScopeProbe::ordered()->get()->last()->created_at);

        expect(Se5q9FScopeProbe::createdAfter('2000-01-01')->count())->toBe(3);
        expect(Se5q9FScopeProbe::createdAfter('2999-01-01')->count())->toBe(0);
        expect(Se5q9FScopeProbe::createdBefore('2999-01-01')->count())->toBe(3);
        expect(Se5q9FScopeProbe::createdBefore('2000-01-01')->count())->toBe(0);

        $asc = Se5q9FScopeProbe::ordered('name', 'asc')->pluck('name')->all();
        expect($asc)->toBe(['Active One', 'Active Two', 'Dormant']);

        $default = Se5q9FScopeProbe::ordered()->first();
        expect($default)->not->toBeNull();
    });
});

describe('SE5Q9: policy infrastructure', function (): void {
    test('SE5Q9-FR-BASE-002: authorize passes an allowed ability and denies a forbidden one', function (): void {
        Gate::define('se5q9-view', fn () => true);
        Gate::define('se5q9-purge', fn () => false);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $probe = new class extends BaseCommandAction
        {
            public function execute(): ActionResponse
            {
                return ActionResponse::ok();
            }

            public function check(string $ability): void
            {
                $this->authorize($ability);
            }
        };

        $probe->check('se5q9-view');
        expect(fn () => $probe->check('se5q9-purge'))->toThrow(AuthorizationException::class);
    });

    test('SE5Q9-FR-BASE-036: super_admin bypasses even a denying policy', function (): void {
        $policy = new Se5q9FProbePolicy;
        $department = Department::factory()->create();

        $super = User::factory()->create();
        $super->assignRole('super_admin');

        $student = User::factory()->create();
        $student->assignRole('student');

        expect($policy->before($super)?->allowed())->toBeTrue();
        expect($policy->before($student))->toBeNull();
        expect($policy->view($student, $department)->allowed())->toBeFalse();
    });

    test('SE5Q9-FR-BASE-037: role vocabulary stays uniform across policies', function (): void {
        $policy = new Se5q9FProbePolicy;

        $super = User::factory()->create();
        $super->assignRole('super_admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $student = User::factory()->create();
        $student->assignRole('student');

        expect($policy->pubIsAdmin($super))->toBeTrue();
        expect($policy->pubIsAdmin($admin))->toBeTrue();
        expect($policy->pubIsAdmin($teacher))->toBeFalse();
        expect($policy->pubIsAdmin($student))->toBeFalse();

        expect($policy->pubCanManage($admin))->toBeTrue();
        expect($policy->pubCanManage($student))->toBeFalse();

        expect($policy->pubHasAny($teacher, ['teacher', 'admin']))->toBeTrue();
        expect($policy->pubHasAny($student, ['teacher', 'admin']))->toBeFalse();
    });

    test('SE5Q9-FR-BASE-038: ownership checks see own records and admins see all', function (): void {
        $policy = new Se5q9FProbePolicy;

        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $stranger->assignRole('student');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $note = new Notification(['user_id' => $owner->id]);

        expect($policy->pubIsOwner($owner, $note))->toBeTrue();
        expect($policy->pubIsOwner($stranger, $note))->toBeFalse();

        expect($policy->pubIsOwnerOrAdmin($owner, $note))->toBeTrue();
        expect($policy->pubIsOwnerOrAdmin($admin, $note))->toBeTrue();
        expect($policy->pubIsOwnerOrAdmin($stranger, $note))->toBeFalse();

        $department = Department::factory()->create();
        $department->setRelation('owner', $owner);

        expect($policy->pubIsRelated($owner, $department, 'owner'))->toBeTrue();
        expect($policy->pubIsRelated($stranger, $department, 'owner'))->toBeFalse();
    });
});

describe('SE5Q9: livewire bases', function (): void {
    test('SE5Q9-FR-BASE-015: manager renders, searches, sorts safely, and selects', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Department::factory()->create(['name' => 'Alpha Dept']);
        Department::factory()->create(['name' => 'Zulu Dept']);
        Department::factory()->create(['name' => 'Mid Dept']);

        Livewire::test(DepartmentManager::class)
            ->assertSee('Alpha Dept')
            ->assertSee('Zulu Dept')
            ->set('search', 'Zulu')
            ->assertSee('Zulu Dept')
            ->assertDontSee('Alpha Dept')
            ->set('search', '')
            ->set('sortBy', ['column' => 'name', 'direction' => 'asc'])
            ->assertSeeInOrder(['Alpha Dept', 'Mid Dept', 'Zulu Dept'])
            ->set('sortBy', ['column' => 'name; DROP TABLE departments; --', 'direction' => 'sideways'])
            ->assertSee('Alpha Dept')
            ->call('selectAll', ['x-1', 'x-2'])
            ->assertSet('selectedIds', ['x-1', 'x-2'])
            ->call('clearSelection')
            ->assertSet('selectedIds', []);

        expect(Department::count())->toBe(3);
    });

    test('SE5Q9-FR-BASE-016: entry modal opens, closes, and maps rejections to toasts', function (): void {
        Livewire::test(Se5q9FEntry::class)
            ->assertSet('showModal', false)
            ->call('create')
            ->assertSet('showModal', true)
            ->assertSet('editingId', null)
            ->call('save', fn () => throw new RejectedException('Duplicate partnership'))
            ->assertDispatched('ts-ui:toast')
            ->call('cancel')
            ->assertSet('showModal', false);
    });

    test('SE5Q9-FR-BASE-017: read-only list searches and paginates without mutations', function (): void {
        Department::factory()->create(['name' => 'RPL List']);
        Department::factory()->create(['name' => 'TKJ List']);

        $list = new Se5q9FList;
        $list->search = 'RPL';

        $rows = $list->rows();

        expect($rows->total())->toBe(1);
        expect($rows->first()->name)->toBe('RPL List');

        $list->search = '';
        expect($list->rows()->total())->toBe(2);

        $list->perPage = 999;
        expect($list->rows()->perPage())->toBe(10);
    });

    test('SE5Q9-FR-BASE-018: form view tracks dirt and clears it only on save', function (): void {
        $form = new Se5q9FForm;

        expect($form->isDirty)->toBeFalse();

        $form->dirty();
        expect($form->isDirty)->toBeTrue();

        $form->save(fn () => null);
        expect($form->isDirty)->toBeFalse();

        $form->dirty();
        $form->save(fn () => throw new RejectedException('Profile rule failed'));
        expect($form->isDirty)->toBeTrue();
    });
});

describe('SE5Q9: notification contract and violation pipeline', function (): void {
    test('SE5Q9-FR-BASE-027: notification action persists through the Core contract', function (): void {
        Event::fake([NotificationSent::class]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $user = User::factory()->create();

        $notification = (new SendNotificationAction)->execute(new NotificationData(
            userId: $user->id,
            type: 'placement',
            title: 'Placement assigned',
            message: 'Check your company',
        ));

        expect($notification->user_id)->toBe($user->id);
        expect($notification->title)->toBe('Placement assigned');
        $this->assertDatabaseHas('notify', [
            'user_id' => $user->id,
            'type' => 'placement',
            'title' => 'Placement assigned',
        ]);
        Event::assertDispatched(NotificationSent::class);
    });

    test('SE5Q9-UC-BASE-002: invalid submissions travel as RejectedException into a toast', function (): void {
        $action = new Se5q9FQuotaAction;

        try {
            $action->execute(['seats' => 0]);
            expect(false)->toBeTrue('expected RejectedException');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe('Placement quota is full');
            expect($e->statusCode())->toBe(400);
        }

        $placed = $action->execute(['seats' => 2]);
        expect($placed->success)->toBeTrue();

        Livewire::test(Se5q9FEntry::class)
            ->call('save', function () use ($action): void {
                $action->execute(['seats' => 0]);
            })
            ->assertDispatched('ts-ui:toast');
    });
});
