<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Logger;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\assertDatabaseHas;

describe('face types', function () {
    it('logs success message', function () {
        Log::spy();

        Logger::success('Operation completed')->systemOnly()->save();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg) => $msg === 'Operation completed');
    });

    it('logs info message', function () {
        Log::spy();

        Logger::info('Something happened')->systemOnly()->save();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg) => $msg === 'Something happened');
    });

    it('logs warning message', function () {
        Log::spy();

        Logger::warning('Disk space low')->systemOnly()->save();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($msg) => $msg === 'Disk space low');
    });

    it('logs error message', function () {
        Log::spy();

        Logger::error('Something broke')->systemOnly()->save();

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg) => $msg === 'Something broke');
    });
});

describe('chaining', function () {
    it('accepts context as second parameter', function () {
        Log::spy();

        Logger::error('Failed', ['file' => 'test.php'])->systemOnly()->save();

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg, $ctx) => ($ctx['file'] ?? null) === 'test.php');
    });

    it('can set causer with for', function () {
        $user = User::factory()->create();

        Logger::success('Logged in')->for($user)->activityOnly()->save();

        assertDatabaseHas('activity_log', [
            'description' => 'Logged in',
            'causer_id' => $user->id,
            'causer_type' => User::class,
        ]);
    });

    it('can set subject with about', function () {
        $user = User::factory()->create();

        Logger::info('User created')->for($user)->about($user)->activityOnly()->save();

        $activity = Activity::where('description', 'User created')->first();
        expect($activity->subject_id)->toBe($user->id);
        expect($activity->subject_type)->toBe(User::class);
    });

    it('can attach payload with withPayload', function () {
        $user = User::factory()->create();

        Logger::warning('High memory')
            ->for($user)
            ->withPayload(['memory' => '85%'])
            ->activityOnly()
            ->save();

        $activity = Activity::where('description', 'High memory')->first();
        expect($activity->properties->get('payload'))->toBe(['memory' => '85%']);
    });

    it('can set log module', function () {
        $user = User::factory()->create();

        Logger::info('Settings changed')
            ->for($user)
            ->module('Admin')
            ->activityOnly()
            ->save();

        $activity = Activity::where('description', 'Settings changed')->first();
        expect($activity->log_name)->toBe('Admin');
    });

    it('can set custom event name', function () {
        $user = User::factory()->create();

        Logger::info('User deleted')
            ->for($user)
            ->event('user_deleted')
            ->activityOnly()
            ->save();

        $activity = Activity::where('description', 'User deleted')->first();
        expect($activity->event)->toBe('user_deleted');
    });

    it('defaults event to the face type', function () {
        $user = User::factory()->create();

        Logger::warning('Deprecated method used')->for($user)->activityOnly()->save();

        $activity = Activity::where('description', 'Deprecated method used')->first();
        expect($activity->event)->toBe('warning');
    });
});

describe('targeting', function () {
    it('logs to both system and activity by default', function () {
        Log::spy();
        $user = User::factory()->create();

        Logger::success('Dual log test')->for($user)->save();

        Log::shouldHaveReceived('info');
        assertDatabaseHas('activity_log', ['description' => 'Dual log test']);
    });

    it('logs to system only with systemOnly', function () {
        Log::spy();
        $user = User::factory()->create();

        Logger::warning('System only')->for($user)->systemOnly()->save();

        Log::shouldHaveReceived('warning');
        expect(Activity::where('description', 'System only')->count())->toBe(0);
    });

    it('logs to activity only with activityOnly', function () {
        Log::spy();
        $user = User::factory()->create();

        Logger::info('Activity only')->for($user)->activityOnly()->save();

        Log::shouldNotHaveReceived('info');
        assertDatabaseHas('activity_log', ['description' => 'Activity only']);
    });
});

describe('smart behavior', function () {
    it('skips activity log when no causer is available', function () {
        Log::spy();

        Logger::error('No user context')->save();

        Log::shouldHaveReceived('error');
        expect(Activity::count())->toBe(0);
    });

    it('includes user info in system log context when causer exists', function () {
        Log::spy();
        $user = User::factory()->create();

        Logger::success('With user')->for($user)->systemOnly()->save();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg, $ctx) => ($ctx['user_id'] ?? null) === $user->id);
    });

    it('includes payload in system log context', function () {
        Log::spy();

        Logger::info('With payload')
            ->withPayload(['key' => 'val'])
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg, $ctx) => ($ctx['payload'] ?? []) === ['key' => 'val']);
    });
});
