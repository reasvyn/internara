<?php

declare(strict_types=1);

use App\Actions\Logbook\DeleteLogbookAction;
use Database\Factories\LogbookFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('deletes a logbook entry', function () {
        $entry = LogbookFactory::new()->create();

        app(DeleteLogbookAction::class)->execute($entry);

        expect($entry->fresh())->toBeNull();
    });
});
