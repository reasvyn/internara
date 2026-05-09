<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\SmartLogger;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\assertDatabaseHas;

describe('face types', function () {
    it('logs success message', function () {
        Log::spy();

        SmartLogger::success('Operation completed')->systemOnly()->save();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg) => $msg === 'Operation completed');
    });

    it('logs info message', function () {
        Log::spy();

        SmartLogger::info('Something happened')->systemOnly()->save();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg) => $msg === 'Something happened');
    });

    it('logs warning message', function () {
        Log::spy();

        SmartLogger::warning('Disk space low')->systemOnly()->save();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($msg) => $msg === 'Disk space low');
    });

    it('logs error message', function () {
        Log::spy();

        SmartLogger::error('Something broke')->systemOnly()->save();

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg) => $msg === 'Something broke');
    });
});

describe('chaining', function () {
    it('accepts context as second parameter', function () {
        Log::spy();

        SmartLogger::error('Failed', ['file' => 'test.php'])->systemOnly()->save();

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg, $ctx) => ($ctx['file'] ?? null) === 'test.php');
    });

    it('can set causer with for', function () {
        $user = User::factory()->create();

        SmartLogger::success('Logged in')->for($user)->activityOnly()->save();

        assertDatabaseHas('activity_log', [
            'description' => 'Logged in',
            'causer_id' => $user->id,
            'causer_type' => User::class,
        ]);
    });

    it('can set subject with about', function () {
        $user = User::factory()->create();

        SmartLogger::info('User created')->for($user)->about($user)->activityOnly()->save();

        $activity = Activity::where('description', 'User created')->first();
        expect($activity->subject_id)->toBe($user->id);
        expect($activity->subject_type)->toBe(User::class);
    });

    it('can attach payload with withPayload', function () {
        $user = User::factory()->create();

        SmartLogger::warning('High memory')
            ->for($user)
            ->withPayload(['memory' => '85%'])
            ->activityOnly()
            ->save();

        $activity = Activity::where('description', 'High memory')->first();
        expect($activity->properties->get('payload'))->toBe(['memory' => '85%']);
    });

    it('can set log module', function () {
        $user = User::factory()->create();

        SmartLogger::info('Settings changed')
            ->for($user)
            ->module('Admin')
            ->activityOnly()
            ->save();

        $activity = Activity::where('description', 'Settings changed')->first();
        expect($activity->log_name)->toBe('Admin');
    });

    it('can set custom event name', function () {
        $user = User::factory()->create();

        SmartLogger::info('User deleted')
            ->for($user)
            ->event('user_deleted')
            ->activityOnly()
            ->save();

        $activity = Activity::where('description', 'User deleted')->first();
        expect($activity->event)->toBe('user_deleted');
    });

    it('defaults event to the face type', function () {
        $user = User::factory()->create();

        SmartLogger::warning('Deprecated method used')->for($user)->activityOnly()->save();

        $activity = Activity::where('description', 'Deprecated method used')->first();
        expect($activity->event)->toBe('warning');
    });
});

describe('targeting', function () {
    it('logs to both system and activity by default', function () {
        Log::spy();
        $user = User::factory()->create();

        SmartLogger::success('Dual log test')->for($user)->save();

        Log::shouldHaveReceived('info');
        assertDatabaseHas('activity_log', ['description' => 'Dual log test']);
    });

    it('logs to system only with systemOnly', function () {
        Log::spy();
        $user = User::factory()->create();

        SmartLogger::warning('System only')->for($user)->systemOnly()->save();

        Log::shouldHaveReceived('warning');
        expect(Activity::where('description', 'System only')->count())->toBe(0);
    });

    it('logs to activity only with activityOnly', function () {
        Log::spy();
        $user = User::factory()->create();

        SmartLogger::info('Activity only')->for($user)->activityOnly()->save();

        Log::shouldNotHaveReceived('info');
        assertDatabaseHas('activity_log', ['description' => 'Activity only']);
    });
});

describe('smart behavior', function () {
    it('skips activity log when no causer is available', function () {
        Log::spy();

        SmartLogger::error('No user context')->save();

        Log::shouldHaveReceived('error');
        expect(Activity::count())->toBe(0);
    });

    it('includes user info in system log context when causer exists', function () {
        Log::spy();
        $user = User::factory()->create();

        SmartLogger::success('With user')->for($user)->systemOnly()->save();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg, $ctx) => ($ctx['user_id'] ?? null) === $user->id);
    });

    it('includes payload in system log context', function () {
        Log::spy();

        SmartLogger::info('With payload')
            ->withPayload(['key' => 'val'])
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg, $ctx) => ($ctx['payload'] ?? []) === ['key' => 'val']);
    });
});

describe('PII masking', function () {
    it('masks sensitive keys in payload with withPiiMasking', function () {
        Log::spy();

        SmartLogger::info('Login attempt')
            ->withPayload([
                'email' => 'user@example.com',
                'password' => 'secret123',
                'ip' => '127.0.0.1',
            ])
            ->withPiiMasking()
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($msg, $ctx) {
                $payload = $ctx['payload'];

                return $payload['password'] === '***'
                    && str_starts_with($payload['email'], 'us***@')
                    && $payload['ip'] === '127.0.0.1';
            });
    });

    it('does not mask payload without withPiiMasking', function () {
        Log::spy();

        SmartLogger::info('Login attempt')
            ->withPayload(['password' => 'secret123', 'email' => 'user@example.com'])
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($msg, $ctx) {
                $payload = $ctx['payload'];

                return $payload['password'] === 'secret123'
                    && $payload['email'] === 'user@example.com';
            });
    });

    it('masks nested sensitive data in payload with withPiiMasking', function () {
        Log::spy();

        SmartLogger::info('Nested test')
            ->withPayload([
                'user' => [
                    'email' => 'john@example.com',
                    'api_token' => 'tok_abc123',
                ],
                'action' => 'update',
            ])
            ->withPiiMasking()
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($msg, $ctx) {
                $nested = $ctx['payload']['user'];

                return str_starts_with($nested['email'], 'jo***@')
                    && $nested['api_token'] === '***'
                    && $ctx['payload']['action'] === 'update';
            });
    });

    it('masks payload in activity log with withPiiMasking', function () {
        $user = User::factory()->create();

        SmartLogger::info('User registered')
            ->for($user)
            ->withPayload(['email' => 'test@example.com', 'password' => 'secret'])
            ->withPiiMasking()
            ->activityOnly()
            ->save();

        $activity = Activity::where('description', 'User registered')->first();
        $payload = $activity->properties->get('payload');

        expect($payload['password'])->toBe('***');
        expect($payload['email'])->toContain('***');
    });
});
