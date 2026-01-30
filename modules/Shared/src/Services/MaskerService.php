<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use Modules\Shared\Services\Contracts\MaskerService as Contract;

class MaskerService implements Contract
{
    /**
     * {@inheritDoc}
     */
    public function email(?string $email): string
    {
        if (empty($email)) {
            return '';
        }

        return (string) preg_replace('/(?<=.{1}).(?=.*@)/', '*', $email);
    }

    /**
     * {@inheritDoc}
     */
    public function sensitive(string $value, int $keepStart = 3, int $keepEnd = 2): string
    {
        $length = strlen($value);

        if ($length <= $keepStart + $keepEnd) {
            return str_repeat('*', $length);
        }

        $start = substr($value, 0, $keepStart);
        $end = substr($value, -$keepEnd);
        $mask = str_repeat('*', $length - $keepStart - $keepEnd);

        return $start.$mask.$end;
    }
}
