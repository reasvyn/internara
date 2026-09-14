<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\MonitoringVisit\Enums;

use App\Modules\Core\Contracts\LabelEnum;

enum VisitMethod: string implements LabelEnum
{
    case SITE_VISIT = 'site_visit';
    case VIRTUAL_MEETING = 'virtual_meeting';
    case PHONE_CALL = 'phone_call';

    public function label(): string
    {
        return match ($this) {
            self::SITE_VISIT => __('journal.visit_method.site_visit'),
            self::VIRTUAL_MEETING => __('journal.visit_method.virtual_meeting'),
            self::PHONE_CALL => __('journal.visit_method.phone_call'),
        };
    }
}
