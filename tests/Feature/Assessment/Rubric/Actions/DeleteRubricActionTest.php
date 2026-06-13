<?php

declare(strict_types=1);

use App\Assessment\Rubric\Actions\DeleteRubricAction;
use App\Assessment\Rubric\Models\Rubric;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes rubric', function () {
    $rubric = Rubric::factory()->create();

    app(DeleteRubricAction::class)->execute($rubric);

    $this->assertModelMissing($rubric);
});
