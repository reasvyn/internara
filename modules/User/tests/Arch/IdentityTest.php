<?php

declare(strict_types=1);

namespace Modules\User\Tests\Arch;

test('identity models should use UUID trait')
    ->expect(['Modules\User\Models\User', 'Modules\Profile\Models\Profile'])
    ->toUse('Modules\Shared\Models\Concerns\HasUuid');

/**
 * ARCHITECTURAL DECISION:
 * Identity anchors (User/Profile) are globally available by design in this ecosystem.
 * Strict isolation for these specific classes is counter-productive and brittle.
 * Sovereignty is instead enforced at the Service layer.
 */
