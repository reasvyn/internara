<?php

declare(strict_types=1);

namespace Tests\Unit\Entities\Setup;

use App\Entities\Setup\SetupState;
use App\Models\Setup;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Mockery;

it('can be instantiated from model', function () {
    $model = Mockery::mock(Setup::class);
    $model->shouldReceive('getAttribute')->with('is_installed')->andReturn(false);
    $model->shouldReceive('getAttribute')->with('setup_token')->andReturn(null);
    $model->shouldReceive('getAttribute')->with('token_expires_at')->andReturn(null);
    $model->shouldReceive('getAttribute')->with('completed_steps')->andReturn([]);

    $entity = SetupState::fromModel($model);

    expect($entity)->toBeInstanceOf(SetupState::class);
});

it('detects installed via file', function () {
    File::shouldReceive('exists')->andReturn(true);

    $model = Mockery::mock(Setup::class);
    $model->shouldReceive('getAttribute')->andReturn(null);

    $entity = SetupState::fromModel($model);
    expect($entity->isInstalled())->toBeTrue();
});

it('detects installed via database', function () {
    File::shouldReceive('exists')->andReturn(false);

    $model = Mockery::mock(Setup::class);
    $model->shouldReceive('getAttribute')->with('is_installed')->andReturn(true);
    $model->shouldReceive('getAttribute')->with('setup_token')->andReturn(null);
    $model->shouldReceive('getAttribute')->with('token_expires_at')->andReturn(null);
    $model->shouldReceive('getAttribute')->with('completed_steps')->andReturn([]);

    $entity = SetupState::fromModel($model);
    expect($entity->isInstalled())->toBeTrue();
});

it('validates setup token', function () {
    $plaintext = 'my-secret-token';
    $encrypted = Crypt::encryptString($plaintext);
    $expiresAt = now()->addHour();

    $model = Mockery::mock(Setup::class);
    $model->shouldReceive('getAttribute')->with('is_installed')->andReturn(false);
    $model->shouldReceive('getAttribute')->with('setup_token')->andReturn($encrypted);
    $model->shouldReceive('getAttribute')->with('token_expires_at')->andReturn($expiresAt);
    $model->shouldReceive('getAttribute')->with('completed_steps')->andReturn([]);

    $entity = SetupState::fromModel($model);

    expect($entity->validateToken($plaintext))->toBeTrue()
        ->and($entity->validateToken('wrong-token'))->toBeFalse();
});

it('rejects expired token', function () {
    $plaintext = 'my-secret-token';
    $encrypted = Crypt::encryptString($plaintext);
    $expiresAt = now()->subHour();

    $model = Mockery::mock(Setup::class);
    $model->shouldReceive('getAttribute')->with('is_installed')->andReturn(false);
    $model->shouldReceive('getAttribute')->with('setup_token')->andReturn($encrypted);
    $model->shouldReceive('getAttribute')->with('token_expires_at')->andReturn($expiresAt);
    $model->shouldReceive('getAttribute')->with('completed_steps')->andReturn([]);

    $entity = SetupState::fromModel($model);

    expect($entity->validateToken($plaintext))->toBeFalse();
});

it('gets current setup step', function () {
    $model = Mockery::mock(Setup::class);
    $model->shouldReceive('getAttribute')->with('is_installed')->andReturn(false);
    $model->shouldReceive('getAttribute')->with('setup_token')->andReturn(null);
    $model->shouldReceive('getAttribute')->with('token_expires_at')->andReturn(null);
    $model->shouldReceive('getAttribute')->with('completed_steps')->andReturn([]);

    $entity = SetupState::fromModel($model);
    expect($entity->getCurrentStep())->toBe('welcome');

    $model = Mockery::mock(Setup::class);
    $model->shouldReceive('getAttribute')->with('is_installed')->andReturn(false);
    $model->shouldReceive('getAttribute')->with('setup_token')->andReturn(null);
    $model->shouldReceive('getAttribute')->with('token_expires_at')->andReturn(null);
    $model->shouldReceive('getAttribute')->with('completed_steps')->andReturn(['welcome']);

    $entity = SetupState::fromModel($model);
    expect($entity->getCurrentStep())->toBe('school');
});

it('detects step completion', function () {
    $model = Mockery::mock(Setup::class);
    $model->shouldReceive('getAttribute')->with('is_installed')->andReturn(false);
    $model->shouldReceive('getAttribute')->with('setup_token')->andReturn(null);
    $model->shouldReceive('getAttribute')->with('token_expires_at')->andReturn(null);
    $model->shouldReceive('getAttribute')->with('completed_steps')->andReturn(['welcome']);

    $entity = SetupState::fromModel($model);
    expect($entity->isStepCompleted('welcome'))->toBeTrue()
        ->and($entity->isStepCompleted('school'))->toBeFalse();
});
