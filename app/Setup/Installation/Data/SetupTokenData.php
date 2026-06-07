<?php

declare(strict_types=1);

namespace App\Setup\Installation\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;

final readonly class SetupTokenData extends BaseData
{
    public function __construct(public string $plaintext, public Carbon $expiresAt) {}
}
