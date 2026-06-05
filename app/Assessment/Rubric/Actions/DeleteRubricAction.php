<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseAction;

final class DeleteRubricAction extends BaseAction
{
    public function execute(Rubric $rubric): void
    {
        $rubric->delete();
    }
}
