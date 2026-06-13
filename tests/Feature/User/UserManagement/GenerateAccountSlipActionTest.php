<?php

declare(strict_types=1);

use App\Auth\ApiTokens\Models\ApiToken;
use App\User\Models\User;
use App\User\UserManagement\Actions\GenerateAccountSlipAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Response;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {});

test('download generates PDF response for single user', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $action = app(GenerateAccountSlipAction::class);
    $response = $action->download($user);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('account-slip-'.$user->username.'.pdf');
});

test('download creates activation token for user', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $action = app(GenerateAccountSlipAction::class);
    $action->download($user);

    expect(ApiToken::where('user_id', $user->id)->where('token_type', 'activation')->exists())->toBeTrue();
});

test('downloadBatch generates PDF response with multiple users', function () {
    $users = User::factory()->count(3)->create();
    $users->each(fn ($u) => $u->assignRole('student'));

    $action = app(GenerateAccountSlipAction::class);
    $response = $action->downloadBatch($users->all());

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('account-slips-batch.pdf');
});

test('downloadBatch creates tokens for all users', function () {
    $users = User::factory()->count(2)->create();
    $users->each(fn ($u) => $u->assignRole('student'));

    $action = app(GenerateAccountSlipAction::class);
    $action->downloadBatch($users->all());

    foreach ($users as $user) {
        expect(ApiToken::where('user_id', $user->id)->where('token_type', 'activation')->exists())->toBeTrue();
    }
});
