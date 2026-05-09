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
    $handbook = HandbookFactory::new()->create();

    expect($handbook)->toBeInstanceOf(Handbook::class)
        ->and($handbook->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $handbook = HandbookFactory::new()->create([
        'is_active' => true,
        'published_at' => now(),
    ]);

    expect($handbook->is_active)->toBeTrue()
        ->and($handbook->published_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('belongs to author', function () {
    $user = UserFactory::new()->create();
    $handbook = HandbookFactory::new()->create(['created_by' => $user->id]);

    expect($handbook->author)->toBeInstanceOf(User::class)
        ->and($handbook->author->id)->toBe($user->id);
});

it('has many acknowledgements', function () {
    $handbook = HandbookFactory::new()->create();
    $acknowledgements = HandbookAcknowledgementFactory::new()->count(2)->create(['handbook_id' => $handbook->id]);

    expect($handbook->acknowledgements)->toHaveCount(2)
        ->and($handbook->acknowledgements->first())->toBeInstanceOf(HandbookAcknowledgement::class);
});

it('delegates isPublished to entity', function () {
    $handbook = HandbookFactory::new()->published()->create();
    expect($handbook->asHandbookPublishState()->isPublished())->toBeTrue();

    $handbook = HandbookFactory::new()->draft()->create();
    expect($handbook->asHandbookPublishState()->isPublished())->toBeFalse();
});
