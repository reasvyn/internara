<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Core\Support\DummyData as CoreDummyData;

/**
 * Backwards-compatibility proxy for Tests\Support\DummyData.
 *
 * The core implementation is located at App\Modules\Core\Support\DummyData
 * so it is available across all environments including production.
 */
class DummyData extends CoreDummyData {}
