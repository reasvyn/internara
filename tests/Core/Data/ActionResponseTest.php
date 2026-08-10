<?php

declare(strict_types=1);

use App\Core\Data\ActionResponse;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('FR-M5: ok() returns a successful response with data and message', function () {
    $response = ActionResponse::ok(['total' => 3], 'Done');

    expect($response)->toBeInstanceOf(ActionResponse::class);
    expect($response->success)->toBeTrue();
    expect($response->data)->toBe(['total' => 3]);
    expect($response->message)->toBe('Done');
    expect($response->failed())->toBeFalse();
});

it('FR-M5: created() and updated() default their messages', function () {
    expect(ActionResponse::created()->message)->toBe(__('common.created'));
    expect(ActionResponse::updated()->message)->toBe(__('common.updated'));
});

it('FR-M5: deleted() returns a successful response', function () {
    $response = ActionResponse::deleted();

    expect($response->success)->toBeTrue();
    expect($response->message)->toBe(__('common.deleted'));
});

it('FR-M5: error() returns a failed response with errors', function () {
    $response = ActionResponse::error('Failed', ['name' => ['required']]);

    expect($response->success)->toBeFalse();
    expect($response->failed())->toBeTrue();
    expect($response->errors)->toBe(['name' => ['required']]);
});

it('FR-M5: withRedirect() preserves the response and sets the redirect', function () {
    $response = ActionResponse::ok('data')->withRedirect('/dashboard');

    expect($response->redirect)->toBe('/dashboard');
    expect($response->success)->toBeTrue();
    expect($response->data)->toBe('data');
});

it('FR-M5: jsonSerialize omits empty and null values', function () {
    $payload = ActionResponse::ok()->jsonSerialize();

    expect($payload)->toBeArray();
    expect($payload['success'])->toBeTrue();
    expect($payload)->not->toHaveKey('data');
});

it('FR-M5: jsonSerialize converts Eloquent models to arrays', function () {
    $user = User::factory()->create();

    $payload = ActionResponse::ok($user)->jsonSerialize();

    expect($payload['data'])->toBeArray();
    expect($payload['data']['id'])->toBe($user->id);
});
