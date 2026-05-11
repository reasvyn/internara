<?php

declare(strict_types=1);

use App\Actions\Guidance\CreateHandbookAction;
use App\Models\Handbook;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeAll(function () {
    require_once getcwd().'/app/Models/Handbook.php';
    class_alias(
        Handbook::class,
        App\Models\Guidance\Handbook::class,
    );
});

describe('execute', function () {
    it('creates a handbook', function () {
        $user = UserFactory::new()->create();

        $handbook = app(CreateHandbookAction::class)->execute($user, [
            'title' => 'Student Handbook 2026',
            'content' => 'Welcome to the internship program.',
            'version' => '1.0',
            'is_active' => true,
        ]);

        expect($handbook)->toBeInstanceOf(Handbook::class)
            ->and($handbook->title)->toBe('Student Handbook 2026')
            ->and($handbook->slug)->toBe('student-handbook-2026')
            ->and($handbook->content)->toBe('Welcome to the internship program.')
            ->and($handbook->version)->toBe('1.0')
            ->and($handbook->is_active)->toBeTrue()
            ->and($handbook->published_at)->not->toBeNull()
            ->and($handbook->created_by)->toBe($user->id);
    });

    it('creates draft handbook when is_active is false', function () {
        $user = UserFactory::new()->create();

        $handbook = app(CreateHandbookAction::class)->execute($user, [
            'title' => 'Draft Handbook',
            'content' => 'Not yet published.',
            'is_active' => false,
        ]);

        expect($handbook->is_active)->toBeFalse()
            ->and($handbook->published_at)->toBeNull();
    });
});
