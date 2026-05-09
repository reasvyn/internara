<?php

declare(strict_types=1);

use App\Models\Handbook;
use App\Models\HandbookAcknowledgement;
use App\Models\User;
use Database\Factories\HandbookAcknowledgementFactory;
use Database\Factories\HandbookFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $acknowledgement = HandbookAcknowledgementFactory::new()->create();

    expect($acknowledgement)->toBeInstanceOf(HandbookAcknowledgement::class)
        ->and($acknowledgement->id)->toBeUuid();
});

it('casts acknowledged_at correctly', function () {
    $acknowledgement = HandbookAcknowledgementFactory::new()->create();

    expect($acknowledgement->acknowledged_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('belongs to user', function () {
    $user = UserFactory::new()->create();
    $acknowledgement = HandbookAcknowledgementFactory::new()->create(['user_id' => $user->id]);

    expect($acknowledgement->user)->toBeInstanceOf(User::class)
        ->and($acknowledgement->user->id)->toBe($user->id);
});

it('belongs to handbook', function () {
    $handbook = HandbookFactory::new()->create();
    $acknowledgement = HandbookAcknowledgementFactory::new()->create(['handbook_id' => $handbook->id]);

    expect($acknowledgement->handbook)->toBeInstanceOf(Handbook::class)
        ->and($acknowledgement->handbook->id)->toBe($handbook->id);
});
