<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * Contract for managing industry partner master data.
 *
 * @extends EloquentQuery<\Modules\Internship\Models\Company>
 */
interface CompanyService extends EloquentQuery {}
