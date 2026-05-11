<?php

declare(strict_types=1);

use App\Actions\Guidance\AcknowledgeHandbookAction;
use App\Models\Handbook;
use Database\Factories\HandbookFactory;
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
    it('creates an acknowledgement record', function () {
        $user = UserFactory::new()->create();
        $handbook = HandbookFactory::new()->create();

        app(AcknowledgeHandbookAction::class)->execute($user, $handbook);

        expect($handbook->acknowledgements()->count())->toBe(1)
            ->and($handbook->acknowledgements()->first()->user_id)->toBe($user->id);
    });
});
