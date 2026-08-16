<?php

declare(strict_types=1);

use App\Core\Actions\BaseProcessAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

uses(LazilyRefreshDatabase::class);

final class TestProcessNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        return ['message' => 'processed'];
    }
}

final class TestProcessAction extends BaseProcessAction
{
    public function execute(): void {}

    public function doStep(string $name, callable $callback): mixed
    {
        return $this->step($name, $callback);
    }

    public function doTrackProgress(float $percent, ?string $message = null): void
    {
        $this->trackProgress($percent, $message);
    }

    public function doGetProgress(): array
    {
        return $this->getProgress();
    }

    public function doGetResults(): array
    {
        return $this->getResults();
    }

    public function doAllSucceeded(): bool
    {
        return $this->allStepsSucceeded();
    }

    public function doNotify(mixed $notifiables, Notification $notification): void
    {
        $this->notify($notifiables, $notification);
    }

    public function doLogProgress(string $action, array $context = []): void
    {
        $this->logProgress($action, $context);
    }
}

test('SE5Q9-FR-A4: step() records success and returns the step result', function () {
    $action = new TestProcessAction;

    expect($action->doStep('import', fn () => 42))->toBe(42);
    expect($action->doGetResults())->toBe(['import' => ['success' => true]]);
});

test('SE5Q9-FR-A4: step() records failure and rethrows the exception', function () {
    $action = new TestProcessAction;

    expect(fn () => $action->doStep('import', fn () => throw new RuntimeException('nope')))
        ->toThrow(RuntimeException::class);
    expect($action->doGetResults()['import']['success'])->toBeFalse();
    expect($action->doGetResults()['import']['error'])->toBe('nope');
});

test('SE5Q9-FR-A4: trackProgress() clamps the percentage between 0 and 100', function () {
    $action = new TestProcessAction;

    $action->doTrackProgress(150, 'over');
    expect($action->doGetProgress())->toBe(['percent' => 100.0, 'message' => 'over']);

    $action->doTrackProgress(-5);
    expect($action->doGetProgress()['percent'])->toBe(0.0);
});

test('SE5Q9-FR-A4: allStepsSucceeded() reflects every step outcome', function () {
    $action = new TestProcessAction;

    $action->doStep('a', fn () => null);
    expect($action->doAllSucceeded())->toBeTrue();

    expect(fn () => $action->doStep('b', fn () => throw new RuntimeException('x')))->toThrow(RuntimeException::class);
    expect($action->doAllSucceeded())->toBeFalse();
});

test('SE5Q9-FR-A4: notify() sends through the Notification facade', function () {
    NotificationFacade::fake();
    $user = User::factory()->create();

    (new TestProcessAction)->doNotify($user, new TestProcessNotification);

    NotificationFacade::assertSentTo($user, TestProcessNotification::class);
});

test('SE5Q9-FR-A4: logProgress() writes progress and step results to the system log', function () {
    $logs = captureLogs();
    $action = new TestProcessAction;

    $action->doStep('parse', fn () => true);
    $action->doTrackProgress(50, 'halfway');
    $action->doLogProgress('test.progress', ['batch' => 1]);

    $entry = $logs->firstWhere('message', 'test.progress');
    expect($entry)->not->toBeNull();
    expect($entry->context['payload']['progress']['percent'])->toBe(50.0);
    expect($entry->context['payload']['steps']['parse']['success'])->toBeTrue();
    expect($entry->context['payload']['batch'])->toBe(1);
});
