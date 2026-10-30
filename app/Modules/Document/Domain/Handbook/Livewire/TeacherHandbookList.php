<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Handbook\Livewire;

class TeacherHandbookList extends StudentHandbookList
{
    public function pageTitle(): string
    {
        return __('handbook.teacher_page_title');
    }

    public function pageDescription(): string
    {
        return __('handbook.teacher_page_description');
    }
}
