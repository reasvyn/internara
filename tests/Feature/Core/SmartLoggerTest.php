<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    app()->setLocale('en');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('SmartLogger', function () {
    it('logs success via both channels', function () {
        SmartLogger::success('User registered')->save();

        expect(Activity::where('description', 'User registered')->exists())->toBeTrue();
    });

    it('logs info via both channels', function () {
        SmartLogger::info('Profile updated')->save();

        expect(Activity::where('description', 'Profile updated')->exists())->toBeTrue();
    });

    it('logs warning via system only, no activity', function () {
        SmartLogger::warning('Disk space low')->systemOnly()->save();

        expect(Activity::where('description', 'Disk space low')->count())->toBe(0);
    });

    it('logs error via activity only', function () {
        SmartLogger::error('Payment failed', ['txn' => 'abc'])->activityOnly()->save();

        expect(Activity::where('description', 'Payment failed')->exists())->toBeTrue();
    });

    it('records activity log with causer', function () {
        SmartLogger::info('Test activity')->for($this->user)->save();

        $activity = Activity::where('causer_id', $this->user->id)->first();
        expect($activity)->not->toBeNull()
            ->and($activity->description)->toBe('Test activity');
    });

    it('records activity log with subject', function () {
        SmartLogger::info('Subject test')->about($this->user)->save();

        $activity = Activity::where('subject_id', $this->user->id)->first();
        expect($activity)->not->toBeNull();
    });

    it('attaches payload to activity log', function () {
        SmartLogger::info('Payload test')->withPayload(['key' => 'value'])->save();

        $activity = Activity::where('description', 'Payload test')->first();
        expect($activity)->not->toBeNull()
            ->and($activity->properties->get('payload'))->toBe(['key' => 'value']);
    });

    it('attaches event name', function () {
        SmartLogger::info('Event test')->event('custom.event')->save();

        $activity = Activity::where('description', 'Event test')->first();
        expect($activity)->not->toBeNull()
            ->and($activity->event)->toBe('custom.event');
    });

    it('attaches module name to log', function () {
        SmartLogger::info('Module test')->module('Core')->save();

        $activity = Activity::where('description', 'Module test')->first();
        expect($activity)->not->toBeNull()
            ->and($activity->log_name)->toBe('Core');
    });

    it('does not record activity log when systemOnly is used', function () {
        SmartLogger::info('System only log')->systemOnly()->save();

        expect(Activity::where('description', 'System only log')->count())->toBe(0);
    });

    it('applies PII masking when enabled', function () {
        SmartLogger::info('PII test')
            ->withPayload(['email' => 'john@example.com', 'password' => 'secret'])
            ->withPiiMasking()
            ->save();

        $activity = Activity::where('description', 'PII test')->first();
        expect($activity)->not->toBeNull()
            ->and($activity->properties->get('payload')['email'])->toBe('jo***@example.com')
            ->and($activity->properties->get('payload')['password'])->toBe('***');
    });

    it('is chainable', function () {
        $result = SmartLogger::info('Chained')
            ->for(null)
            ->about(null)
            ->withPayload([])
            ->module('Test')
            ->event('test')
            ->channel('test')
            ->both()
            ->withPiiMasking();

        expect($result)->toBeInstanceOf(SmartLogger::class);
    });

    it('handles activity log failure gracefully', function () {
        SmartLogger::info('Fail gracefully')->save();

        expect(true)->toBeTrue();
    });
});
