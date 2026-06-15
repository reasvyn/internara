<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;

final class DeleteRubricAction extends BaseCommandAction
{
    public function execute(Rubric $rubric): void
    {
        $rubric->delete();
    }
}
