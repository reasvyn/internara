<?php

declare(strict_types=1);

use App\Modules\Core\Events\BaseEvent;

function eventSystemEventsOnDisk(): array
{
    $classes = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path(), RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        if (! str_contains($file->getPathname(), '/Events/')) {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        if ($content === false || ! preg_match('/^namespace\s+(.+?);$/m', $content, $match)) {
            continue;
        }

        $fqcn = $match[1].'\\'.$file->getBasename('.php');

        if ($fqcn === BaseEvent::class) {
            continue;
        }

        $classes[] = $fqcn;
    }

    sort($classes);

    return $classes;
}

test('NUCY3-FR-EV1: every event class in the codebase extends BaseEvent', function () {
    $events = eventSystemEventsOnDisk();

    expect($events)->not->toBeEmpty();

    foreach ($events as $event) {
        expect(is_subclass_of($event, BaseEvent::class))
            ->toBeTrue("{$event} must extend ".BaseEvent::class);
    }
});

test('NUCY3-FR-EV5/NFR-EV5: every config/event.php mapping is boot-valid', function () {
    $mappings = config('event.listen');

    expect($mappings)->toBeArray()->not->toBeEmpty();

    foreach ($mappings as $event => $listeners) {
        expect(is_subclass_of($event, BaseEvent::class))
            ->toBeTrue("{$event} must extend ".BaseEvent::class);
        expect($listeners)->toBeArray()->not->toBeEmpty();

        foreach ($listeners as $listener) {
            expect(class_exists($listener))->toBeTrue("Listener {$listener} must exist");
        }
    }
});

test('NUCY3-FR-EV6: every registered event has at least one listener', function () {
    foreach (config('event.listen') as $event => $listeners) {
        expect($listeners)->not->toBeEmpty("Event {$event} must have a listener");
    }
});

test('NUCY3-FR-EV6: every config-registered listener is wired to a real event class', function () {
    $mappings = config('event.listen');

    foreach ($mappings as $event => $listeners) {
        foreach ($listeners as $listener) {
            expect(is_subclass_of($event, BaseEvent::class))
                ->toBeTrue("{$event} must extend ".BaseEvent::class);
            expect(is_string($listener))->toBeTrue();
        }
    }
});
