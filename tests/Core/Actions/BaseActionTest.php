<?php

declare(strict_types=1);

use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use App\Core\Events\BaseEvent;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

final class TestActionEvent extends BaseEvent
{
    public function __construct(public Model $subject) {}

    public function eventName(): string
    {
        return 'test.action_completed';
    }
}

final class TestAction extends BaseCommandAction
{
    public function __construct(private readonly Closure $callback) {}

    public function execute(): ActionResponse
    {
        return $this->transaction(function () {
            ($this->callback)();
            $this->log('test.action_completed');

            return ActionResponse::ok(true);
        });
    }

    public function executeNested(): ActionResponse
    {
        return $this->transaction(fn () => $this->execute());
    }

    public function runWithEvent(BaseEvent $event): ActionResponse
    {
        return $this->transaction(function () use ($event) {
            $this->dispatchEvent($event);

            return ActionResponse::ok(true);
        });
    }

    public function failNow(): never
    {
        $this->fail('Business rule violated', ['field' => 'value']);
    }
}

it('FR-A6: execute() wraps the mutation in a transaction that commits', function () {
    (new TestAction(fn () => User::factory()->create()))->execute();

    expect(User::count())->toBe(1);
});

it('FR-A1: a failing callback rolls back the transaction', function () {
    DB::rollBack();

    $action = new TestAction(function () {
        User::factory()->create();

        throw new RuntimeException('boom');
    });

    expect(fn () => $action->execute())->toThrow(RuntimeException::class);
    expect(User::count())->toBe(0);

    DB::beginTransaction();
});

it('FR-A1: nested transactions run without double-wrapping', function () {
    (new TestAction(fn () => User::factory()->create()))->executeNested();

    expect(User::count())->toBe(1);
});

it('FR-A7: execute() logs a successful mutation to the activity log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    (new TestAction(fn () => null))->execute();

    expect(DB::table('activity_log')->where('description', 'test.action_completed')->count())->toBe(1);
});

it('FR-A1: fail() throws a RejectedException carrying context', function () {
    $action = new TestAction(fn () => null);

    try {
        $action->failNow();
        $this->fail('Expected RejectedException');
    } catch (RejectedException $e) {
        expect($e->getMessage())->toBe('Business rule violated');
        expect($e->getContext())->toBe(['field' => 'value']);
    }
});

it('FR-A1: queued events dispatch after the transaction commits', function () {
    Event::fake();
    $subject = User::factory()->create();

    (new TestAction(fn () => null))->runWithEvent(new TestActionEvent($subject));

    Event::assertDispatched(TestActionEvent::class);
});
