<?php

declare(strict_types=1);

namespace App\SysAdmin\UserManagement\Actions;

use App\Core\Actions\BaseAction;
use Illuminate\Support\Facades\File;

final class ReadRecoveryKeyAction extends BaseAction
{
    public function execute(): ?string
    {
        $path = storage_path('app/private/.recovery-key');

        if (! File::exists($path)) {
            return null;
        }

        $content = File::get($path);

        foreach (explode(PHP_EOL, $content) as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            return $trimmed;
        }

        return null;
    }
}
