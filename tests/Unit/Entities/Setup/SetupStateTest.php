<?php

declare(strict_types=1);

namespace Tests\Unit\Entities\Setup;

use App\Entities\Setup\SetupState;
use App\Models\Setup;
use Mockery;

afterEach(function () {
    Mockery::close();
});

it('can be instantiated from model', function () {
    $model = Mockery::mock(Setup::class);
    $model->allows()->getAttribute('is_installed')->andReturn(false);
    $model->allows()->getAttribute('setup_token')->andReturn(null);
    $model->allows()->getAttribute('token_expires_at')->andReturn(null);
    $model->allows()->getAttribute('completed_steps')->andReturn([]);
    $model->allows()->getAttribute('recovery_key')->andReturn(null);
    $model->allows()->getAttribute('updated_at')->andReturn(null);

    $entity = SetupState::fromModel($model);

    expect($entity)->toBeInstanceOf(SetupState::class);
});

it('detects installed via database', function () {
    $model = Mockery::mock(Setup::class);
    $model->allows()->getAttribute('is_installed')->andReturn(true);
    $model->allows()->getAttribute('setup_token')->andReturn(null);
    $model->allows()->getAttribute('token_expires_at')->andReturn(null);
    $model->allows()->getAttribute('completed_steps')->andReturn([]);
    $model->allows()->getAttribute('recovery_key')->andReturn(null);
    $model->allows()->getAttribute('updated_at')->andReturn(null);

    $entity = SetupState::fromModel($model);
    expect($entity->isInstalled())->toBeTrue();
});

it('detects not installed', function () {
    $model = Mockery::mock(Setup::class);
    $model->allows()->getAttribute('is_installed')->andReturn(false);
    $model->allows()->getAttribute('setup_token')->andReturn(null);
    $model->allows()->getAttribute('token_expires_at')->andReturn(null);
    $model->allows()->getAttribute('completed_steps')->andReturn([]);
    $model->allows()->getAttribute('recovery_key')->andReturn(null);
    $model->allows()->getAttribute('updated_at')->andReturn(null);

    $entity = SetupState::fromModel($model);
    expect($entity->isInstalled())->toBeFalse();
});

it('validates setup token', function () {
    $plaintext = 'my-secret-token';
    $expiresAt = now()->addHour();

    $model = Mockery::mock(Setup::class);
    $model->allows()->getAttribute('is_installed')->andReturn(false);
    $model->allows()->getAttribute('setup_token')->andReturn($plaintext);
    $model->allows()->getAttribute('token_expires_at')->andReturn($expiresAt);
    $model->allows()->getAttribute('completed_steps')->andReturn([]);
    $model->allows()->getAttribute('recovery_key')->andReturn(null);
    $model->allows()->getAttribute('updated_at')->andReturn(null);

    $entity = SetupState::fromModel($model);

    expect($entity->validateToken($plaintext, 'my-secret-token', now()))->toBeTrue()
        ->and($entity->validateToken($plaintext, 'wrong-token', now()))->toBeFalse();
});

it('rejects expired token', function () {
    $plaintext = 'my-secret-token';
    $expiresAt = now()->subHour();

    $model = Mockery::mock(Setup::class);
    $model->allows()->getAttribute('is_installed')->andReturn(false);
    $model->allows()->getAttribute('setup_token')->andReturn($plaintext);
    $model->allows()->getAttribute('token_expires_at')->andReturn($expiresAt);
    $model->allows()->getAttribute('completed_steps')->andReturn([]);
    $model->allows()->getAttribute('recovery_key')->andReturn(null);
    $model->allows()->getAttribute('updated_at')->andReturn(null);

    $entity = SetupState::fromModel($model);

    expect($entity->validateToken($plaintext, $plaintext, now()))->toBeFalse();
});

it('detects step completion', function () {
    $model = Mockery::mock(Setup::class);
    $model->allows()->getAttribute('is_installed')->andReturn(false);
    $model->allows()->getAttribute('setup_token')->andReturn(null);
    $model->allows()->getAttribute('token_expires_at')->andReturn(null);
    $model->allows()->getAttribute('completed_steps')->andReturn(['welcome']);
    $model->allows()->getAttribute('recovery_key')->andReturn(null);
    $model->allows()->getAttribute('updated_at')->andReturn(null);

    $entity = SetupState::fromModel($model);
    expect($entity->isStepCompleted('welcome'))->toBeTrue()
        ->and($entity->isStepCompleted('school'))->toBeFalse();
});
