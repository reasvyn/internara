<?php

declare(strict_types=1);

use App\Settings\Actions\SetSettingAction;
use App\Settings\Events\SettingUpdated;
use Tests\Support\WithSettingsSeed;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);
uses(WithSettingsSeed::class);

test('event is dispatched when setting is created via SetSettingAction', function () {
    Event::fake([SettingUpdated::class]);

    app(SetSettingAction::class)->execute(
        key: 'test.event_key',
        value: 'test',
        group: 'test',
        type: 'string',
    );

    Event::assertDispatched(SettingUpdated::class, function (SettingUpdated $event) {
        return $event->setting->key === 'test.event_key' && $event->wasRecentlyCreated === true;
    });
});
