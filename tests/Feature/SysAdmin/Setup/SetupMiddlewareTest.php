<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\Academics\Http\Middleware\ProtectSetupRouteMiddleware;
use App\Academics\Http\Middleware\RequireSetupAccessMiddleware;
use App\Core\Support\CacheKeys;
use App\SysAdmin\Setup\Actions\GenerateSetupTokenAction;
use App\SysAdmin\Setup\Models\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| RequireSetupAccessMiddleware
|--------------------------------------------------------------------------
|
| Global middleware that redirects all non-setup requests to /setup
| when the system is not installed. Bypasses Livewire requests and
| static asset requests.
|
*/

beforeEach(function () {
    Setup::query()->delete();
    Cache::clear();
});

test('RequireSetupAccess redirects to setup when system is not installed', function () {
    Setup::create(['is_installed' => false, 'completed_steps' => []]);

    $response = $this->get('/login');

    $response->assertRedirect(route('setup'));
});

test('RequireSetupAccess passes through when system is installed', function () {
    Setup::create(['is_installed' => true, 'completed_steps' => []]);

    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('RequireSetupAccess does not redirect the setup route itself', function () {
    Setup::create(['is_installed' => false, 'completed_steps' => []]);

    // Accessing /setup should not cause a redirect loop
    $response = $this->get('/setup');

    $response->assertStatus(200);
});

test('RequireSetupAccess bypasses Livewire requests when not installed', function () {
    Setup::create(['is_installed' => false, 'completed_steps' => []]);

    $middleware = app(RequireSetupAccessMiddleware::class);
    $request = Request::create('/livewire/update', 'POST');
    $request->headers->set('X-Livewire', 'true');

    $response = $middleware->handle($request, fn () => new Response('livewire-ok', 200));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('livewire-ok');
});

test('RequireSetupAccess caches the installed state', function () {
    Setup::create(['is_installed' => true, 'completed_steps' => []]);

    // First request warms the cache
    $this->get('/login');

    expect(Cache::has(CacheKeys::SETUP_INSTALLED))->toBeTrue();

    // Even if we change DB directly, cache should keep the old value
    Setup::query()->update(['is_installed' => false]);

    $middleware = app(RequireSetupAccessMiddleware::class);
    $request = Request::create('/dashboard', 'GET');

    $response = $middleware->handle($request, fn () => new Response('ok', 200));

    // Should still pass through because cache says installed
    expect($response->getStatusCode())->toBe(200);
});

test('RequireSetupAccess redirects after cache is cleared', function () {
    Setup::create(['is_installed' => false, 'completed_steps' => []]);
    Cache::clear();

    $response = $this->get('/login');

    $response->assertRedirect(route('setup'));
});

/*
|--------------------------------------------------------------------------
| ProtectSetupRouteMiddleware — Self-Destruction (404)
|--------------------------------------------------------------------------
|
| Once the system is installed, the setup route self-destructs and
| returns 404 to prevent re-access. Only a brief finalization window
| allows the completion step to be shown.
|
*/

test('ProtectSetupRoute returns 404 when system is already installed', function () {
    Setup::create(['is_installed' => true, 'completed_steps' => []]);

    $response = $this->get('/setup');

    $response->assertStatus(404);
});

test('ProtectSetupRoute returns 404 even with setup.authorized session when installed', function () {
    Setup::create(['is_installed' => true, 'completed_steps' => []]);

    $response = $this->withSession(['setup.authorized' => true])
        ->get('/setup');

    $response->assertStatus(404);
});

test('ProtectSetupRoute clears all setup session data on self-destruction', function () {
    $setup = Setup::create([
        'is_installed' => true,
        'completed_steps' => [],
    ]);
    Setup::query()->where('id', $setup->id)->update([
        'updated_at' => now()->subMinutes(5),
    ]);

    Session::put('setup.authorized', true);
    Session::put('setup.token', 'some-token');
    Session::put('setup.token_input', 'input');
    Session::put('setup.form_data', ['school' => ['name' => 'Test']]);

    try {
        $this->withSession([
            'setup.authorized' => true,
            'setup.token' => 'some-token',
            'setup.token_input' => 'input',
            'setup.form_data' => ['school' => ['name' => 'Test']],
        ])->get('/setup');
    } catch (NotFoundHttpException) {
        // Expected — 404 abort
    }

    // Session data should be forgotten
    expect(Session::get('setup.authorized'))->toBeNull();
    expect(Session::get('setup.token'))->toBeNull();
    expect(Session::get('setup.form_data'))->toBeNull();
});

test('ProtectSetupRoute allows access during finalization window after completion', function () {
    Setup::create([
        'is_installed' => true,
        'completed_steps' => [],
        'updated_at' => now(), // just installed
    ]);

    $windowSeconds = (int) config('setup.security.finalization_window_seconds', 30);

    // With setup.completed session AND within the finalization window, should pass through
    $response = $this->withSession(['setup.completed' => true])
        ->get('/setup');

    $response->assertStatus(200);
});

test('ProtectSetupRoute returns 404 after finalization window expires', function () {
    $setup = Setup::create([
        'is_installed' => true,
        'completed_steps' => [],
    ]);
    Setup::query()->where('id', $setup->id)->update([
        'updated_at' => now()->subMinutes(5),
    ]);

    $response = $this->withSession(['setup.completed' => true])
        ->get('/setup');

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| ProtectSetupRouteMiddleware — Token Authentication
|--------------------------------------------------------------------------
|
| Before setup is installed, the middleware requires a valid setup token
| to grant access. Tokens are single-use and rate-limited.
|
*/

test('ProtectSetupRoute shows enter-code view when no token and not authorized', function () {
    Setup::create(['is_installed' => false, 'completed_steps' => []]);
    RateLimiter::clear('setup:127.0.0.1');

    $middleware = app(ProtectSetupRouteMiddleware::class);
    $request = Request::create('/setup', 'GET');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    $response = $middleware->handle($request, fn () => new Response('wizard', 200));

    expect($response->getStatusCode())->toBe(200);
    // Should render the enter-code view, not the wizard
    expect($response->getContent())->not->toBe('wizard');
});

test('ProtectSetupRoute passes through when session is authorized', function () {
    Setup::create(['is_installed' => false, 'completed_steps' => []]);

    Session::put('setup.authorized', true);

    $middleware = app(ProtectSetupRouteMiddleware::class);
    $request = Request::create('/setup', 'GET');

    $response = $middleware->handle($request, fn () => new Response('wizard-content', 200));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('wizard-content');
});

test('ProtectSetupRoute grants access with valid token and sets session', function () {
    $setup = Setup::create(['is_installed' => false, 'completed_steps' => []]);

    $tokenResult = app(GenerateSetupTokenAction::class)->execute();
    $plaintext = $tokenResult['plaintext'];

    $response = $this->get('/setup?setup_token='.$plaintext);

    $response->assertStatus(200);
    expect(Session::get('setup.authorized'))->toBeTrue();
});

test('ProtectSetupRoute rejects invalid token', function () {
    Setup::create([
        'is_installed' => false,
        'completed_steps' => [],
        'setup_token' => Crypt::encryptString('real-token'),
        'token_expires_at' => now()->addHour(),
    ]);

    RateLimiter::clear('setup:127.0.0.1');

    $response = $this->get('/setup?setup_token=wrong-token');

    $response->assertStatus(403);
});

test('ProtectSetupRoute rate limits after too many invalid attempts', function () {
    Setup::create([
        'is_installed' => false,
        'completed_steps' => [],
        'setup_token' => Crypt::encryptString('real-token'),
        'token_expires_at' => now()->addHour(),
    ]);

    $maxAttempts = (int) config('setup.security.rate_limit_attempts', 20);
    $key = 'setup:127.0.0.1';

    RateLimiter::clear($key);

    // Exhaust rate limit
    for ($i = 0; $i < $maxAttempts; $i++) {
        RateLimiter::hit($key, 60);
    }

    $response = $this->get('/setup?setup_token=bad-token');

    $response->assertStatus(429);
});

test('ProtectSetupRoute POST with valid token redirects to GET setup', function () {
    Setup::create(['is_installed' => false, 'completed_steps' => []]);

    $tokenResult = app(GenerateSetupTokenAction::class)->execute();

    $response = $this->post('/setup', ['setup_token' => $tokenResult['plaintext']]);

    $response->assertRedirect(route('setup'));
    expect(Session::get('setup.authorized'))->toBeTrue();
});

test('ProtectSetupRoute returns JSON 403 for Livewire requests without authorization', function () {
    Setup::create([
        'is_installed' => false,
        'completed_steps' => [],
        'setup_token' => Crypt::encryptString('real-token'),
        'token_expires_at' => now()->addHour(),
    ]);

    RateLimiter::clear('setup:127.0.0.1');

    $middleware = app(ProtectSetupRouteMiddleware::class);
    $request = Request::create('/setup', 'POST');
    $request->headers->set('X-Livewire', 'true');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    $response = $middleware->handle($request, fn () => new Response('ok', 200));

    expect($response->getStatusCode())->toBe(403);
    $data = json_decode($response->getContent(), true);
    expect($data)->toHaveKey('redirect');
    expect($data['redirect'])->toBe(route('login'));
});

test('ProtectSetupRoute rejects expired token', function () {
    Setup::create([
        'is_installed' => false,
        'completed_steps' => [],
        'setup_token' => Crypt::encryptString('expired-token'),
        'token_expires_at' => now()->subHour(), // expired
    ]);

    RateLimiter::clear('setup:127.0.0.1');

    $response = $this->get('/setup?setup_token=expired-token');

    $response->assertStatus(403);
});

test('ProtectSetupRoute consumes token after successful validation (single-use)', function () {
    Setup::create(['is_installed' => false, 'completed_steps' => []]);

    $tokenResult = app(GenerateSetupTokenAction::class)->execute();
    $plaintext = $tokenResult['plaintext'];

    // First use: succeeds
    $this->get('/setup?setup_token='.$plaintext);

    // Token should be consumed (nullified in DB)
    $setup = Setup::first();
    expect($setup->setup_token)->toBeNull();
    expect($setup->token_expires_at)->toBeNull();
});
