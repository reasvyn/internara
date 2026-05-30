<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire\Dashboards;

use App\Domain\Admin\Actions\GetAdminDashboardStatsAction;
use App\Domain\User\Livewire\UserDashboard;
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

        $this->readiness = [
            'database' => ['label' => __('dashboard.readiness.database'), 'passed' => $this->checkDatabase()],
            'mail' => ['label' => __('dashboard.readiness.mail'), 'passed' => $this->checkMail()],
            'cache' => ['label' => __('dashboard.readiness.cache'), 'passed' => $this->checkCache()],
            'queue' => ['label' => __('dashboard.readiness.queue'), 'passed' => $this->checkQueue()],
            'storage' => ['label' => __('dashboard.readiness.storage'), 'passed' => $this->checkStorage()],
        ];
    }

    public function render(): View
    {
        return view('user.dashboards.admin', [
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
            Cache::store()->put('health_check', true, 1);
            $val = Cache::store()->get('health_check');

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
        return is_link(public_path('storage'))
            && File::isWritable(storage_path('logs'))
            && File::isWritable(storage_path('framework/cache'));
    }
}
