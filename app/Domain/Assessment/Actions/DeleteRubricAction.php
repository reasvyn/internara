<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\Rubric;
use App\Domain\Core\Actions\BaseAction;

class DeleteRubricAction extends BaseAction
{
    public function execute(Rubric $rubric): void
    {
        $rubric->delete();
    }
}
