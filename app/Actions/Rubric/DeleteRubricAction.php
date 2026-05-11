<?php

declare(strict_types=1);

namespace App\Actions\Rubric;

use App\Models\Rubric;

class DeleteRubricAction
{
    public function execute(Rubric $rubric): void
    {
        $rubric->delete();
    }
}
