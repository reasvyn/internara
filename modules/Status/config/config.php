<?php

declare(strict_types=1);
use Modules\Status\Models\Status;

return [
    'name' => 'Status',

    /**
     * Model used for tracking status history.
     */
    'status_model' => Status::class,
];
