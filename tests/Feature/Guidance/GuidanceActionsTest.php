<?php

declare(strict_types=1);

use App\Domain\Guidance\Actions\AcknowledgeHandbookAction;
use App\Domain\Guidance\Actions\CreateHandbookAction;
use App\Domain\Guidance\Models\Handbook;
use App\Domain\User\Models\User;

describe('CreateHandbookAction', function () {
    it('creates a new handbook', function () {
        $user = User::factory()->create();

        $handbook = app(CreateHandbookAction::class)->execute($user, [
            'title' => 'Internship Guidelines',
            'content' => 'All the rules and regulations.',
            'version' => '2.0',
            'is_active' => true,
        ]);

        expect($handbook)->toBeInstanceOf(Handbook::class)
            ->and($handbook->title)->toBe('Internship Guidelines')
            ->and($handbook->slug)->toBe('internship-guidelines')
            ->and($handbook->version)->toBe('2.0')
            ->and($handbook->is_active)->toBeTrue()
            ->and($handbook->published_at)->not->toBeNull()
            ->and($handbook->created_by)->toBe($user->id);
    });

    it('creates an inactive draft handbook', function () {
        $user = User::factory()->create();

        $handbook = app(CreateHandbookAction::class)->execute($user, [
            'title' => 'Draft Policy',
            'content' => 'Not yet published.',
            'is_active' => false,
        ]);

        expect($handbook->is_active)->toBeFalse()
            ->and($handbook->published_at)->toBeNull();
    });
});

describe('AcknowledgeHandbookAction', function () {
    it('records user acknowledgement of a handbook', function () {
        $user = User::factory()->create();
        $handbook = Handbook::factory()->create();

        app(AcknowledgeHandbookAction::class)->execute($user, $handbook);

        expect($handbook->acknowledgements()->count())->toBe(1)
            ->and($handbook->acknowledgements()->first()->user_id)->toBe($user->id);
    });
});
