<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Installation;

use App\Settings\Services\Settings;
use App\Setup\Installation\Actions\GenerateSetupTokenAction;
use App\Setup\Installation\Data\SetupTokenData;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(LazilyRefreshDatabase::class);

test('generate setup token action creates token and increments version', function () {
    $action = app(GenerateSetupTokenAction::class);

    $result = $action->execute();

    expect($result)->toBeInstanceOf(SetupTokenData::class);
    expect($result->plaintext)->not->toBeEmpty();
    expect($result->expiresAt)->not->toBeNull();

    $token = Settings::get('setup.install_token');
    expect($token)->not->toBeNull();
    expect(Crypt::decryptString($token))->toBe($result->plaintext);
    expect(Settings::get('setup.token_version'))->toBe(1);
});
