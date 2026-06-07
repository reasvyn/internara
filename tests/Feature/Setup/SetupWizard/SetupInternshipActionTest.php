<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\SetupWizard\Actions;

use App\Program\Internship\Actions\CreateInternshipAction;
use App\Setup\SetupWizard\Actions\SetupInternshipAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setup internship action delegates to create internship action', function () {
    $createAction = app(CreateInternshipAction::class);
    $action = new SetupInternshipAction($createAction);

    expect($action)->toBeInstanceOf(SetupInternshipAction::class);
});
