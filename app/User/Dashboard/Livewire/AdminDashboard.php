<?php

declare(strict_types=1);

namespace App\User\Dashboard\Livewire;

use App\Support\CacheKeys;
use App\SysAdmin\Actions\GetAdminDashboardStatsAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdminDashboard extends UserDashboard
{
    public array $stats = [];

    public array $readiness = [];

    public function mount(GetAdminDashboardStatsAction $statsAction): void
    {
        $this->stats = $statsAction->execute();

        $dbOk = $this->checkDatabase();
        $mailOk = $this->checkMail();
        $cacheOk = $this->checkCache();
        $queueOk = $this->checkQueue();
        $storageOk = $this->checkStorage();

        $this->readiness = [
            'database' => [
                'label' => __('dashboard.readiness.database'),
                'passed' => $dbOk,
                'status' => $dbOk
                    ? __('dashboard.readiness.connected')
                    : __('dashboard.readiness.disconnected'),
            ],
            'mail' => [
                'label' => __('dashboard.readiness.mail'),
                'passed' => $mailOk,
                'status' => $mailOk
                    ? __('dashboard.readiness.configured')
                    : __('dashboard.readiness.not_configured'),
            ],
            'cache' => [
                'label' => __('dashboard.readiness.cache'),
                'passed' => $cacheOk,
                'status' => $cacheOk
                    ? __('dashboard.readiness.responding')
                    : __('dashboard.readiness.not_responding'),
            ],
            'queue' => [
                'label' => __('dashboard.readiness.queue'),
                'passed' => $queueOk,
                'status' => $queueOk
                    ? __('dashboard.readiness.ready')
                    : __('dashboard.readiness.unavailable'),
            ],
            'storage' => [
                'label' => __('dashboard.readiness.storage'),
                'passed' => $storageOk,
                'status' => $storageOk
                    ? __('dashboard.readiness.linked')
                    : __('dashboard.readiness.missing'),
            ],
        ];
    }

    public function render(): View
    {
        return view('user.dashboard.admin', [
            'roleContent' => true,
            'stats' => $this->stats,
            'readiness' => $this->readiness,
        ]);
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
        $mailer = config('mail.default');
        $host = config('mail.mailers.'.$mailer.'.host', '');

        if ($mailer === 'log') {
            return true;
        }

        return $host !== '' && $host !== '127.0.0.1';
    }

    private function checkCache(): bool
    {
        try {
            Cache::store()->put(CacheKeys::HEALTH_CHECK, true, 1);
            $val = Cache::store()->get(CacheKeys::HEALTH_CHECK);

            return $val === true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        $connection = config('queue.default', 'sync');

        if ($connection === 'sync') {
            return true;
        }

        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        return is_link(public_path('storage')) &&
            File::isWritable(storage_path('logs')) &&
            File::isWritable(storage_path('framework/cache'));
    }
}
