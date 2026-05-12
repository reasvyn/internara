<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemAuditService
{
    /** @return array<string, bool> */
    public function run(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'mail' => $this->checkMail(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkMail(): bool
    {
        return config('mail.mailers.smtp.transport') === 'smtp'
            && config('mail.mailers.smtp.host') !== null
            && config('mail.from.address') !== null;
    }

    private function checkCache(): bool
    {
        try {
            Cache::store()->has('health-check');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        return config('queue.default') !== 'sync';
    }

    private function checkStorage(): bool
    {
        try {
            return Storage::disk('public')->exists('/');
        } catch (\Throwable) {
            return false;
        }
    }
}
