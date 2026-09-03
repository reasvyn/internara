<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Dashboard\Livewire;

use App\Modules\SysAdmin\Actions\ReadAdminDashboardAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Livewire\Attributes\Layout;

#[Layout('ui::layouts.app')]
class AdminDashboard extends UserDashboard
{
    public array $stats = [];

    public array $readiness = [];

    public array $pipelineStages = [];

    public int $pipelineMaxV = 1;

    public int $pipelineThroughput = 0;

    public int $pipelineAbsorption = 0;

    public int $pipelineCompletionRate = 0;

    public int $pipelineBottleneck = 0;

    public string $throughputClass = 'text-base-content/60';

    public string $bottleneckClass = 'text-base-content';

    public array $registrationFunnel = [];

    public array $activityMetrics = [];

    public array $completionMetrics = [];

    public bool $isSuperAdmin = false;

    public int $totalUsersCombined = 0;

    public string $failedLoginsClass = '';

    public function mount(ReadAdminDashboardAction $statsAction): void
    {
        $this->stats = $statsAction->execute();
        $this->preparePipelineMetrics();
        $this->prepareFunnelMetrics();
        $this->totalUsersCombined = ($this->stats['totalStudents'] ?? 0) + ($this->stats['totalTeachers'] ?? 0) + ($this->stats['totalSupervisors'] ?? 0);
        $this->failedLoginsClass = ($this->stats['failedLogins7d'] ?? 0) > 0 ? 'text-error' : '';

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
            'pipelineStages' => $this->pipelineStages,
            'pipelineMaxV' => $this->pipelineMaxV,
            'pipelineThroughput' => $this->pipelineThroughput,
            'pipelineAbsorption' => $this->pipelineAbsorption,
            'pipelineCompletionRate' => $this->pipelineCompletionRate,
            'pipelineBottleneck' => $this->pipelineBottleneck,
            'throughputClass' => $this->throughputClass,
            'bottleneckClass' => $this->bottleneckClass,
            'registrationFunnel' => $this->registrationFunnel,
            'activityMetrics' => $this->activityMetrics,
            'completionMetrics' => $this->completionMetrics,
            'isSuperAdmin' => $this->isSuperAdmin,
            'totalUsersCombined' => $this->totalUsersCombined,
            'failedLoginsClass' => $this->failedLoginsClass,
        ]);
    }

    private function preparePipelineMetrics(): void
    {
        $this->isSuperAdmin = (bool) auth()->user()?->hasRole('super_admin');

        $totalSt = $this->stats['totalStudents'] ?? 0;
        $completed = $this->stats['certificatesIssued'] ?? 0;
        $registered = $this->stats['registrationsTotal'] ?? 0;
        $placed = $this->stats['placementFilled'] ?? 0;
        $active = $this->stats['registrationsActive'] ?? 0;

        $stages = [
            ['label' => __('dashboard.pipeline.students'), 'v' => $totalSt, 'c' => 'bg-base-content/20'],
            ['label' => __('dashboard.pipeline.registered'), 'v' => $registered, 'c' => 'bg-warning'],
            ['label' => __('dashboard.pipeline.placed'), 'v' => $placed, 'c' => 'bg-primary'],
            ['label' => __('dashboard.pipeline.active'), 'v' => $active, 'c' => 'bg-info'],
            ['label' => __('dashboard.pipeline.completed'), 'v' => $completed, 'c' => 'bg-success'],
        ];

        $maxV = max(array_column($stages, 'v')) ?: 1;

        foreach ($stages as $i => &$stage) {
            $prevV = $i > 0 ? $stages[$i - 1]['v'] : $stage['v'];
            $drop = $prevV > 0 ? (int) round((1 - $stage['v'] / $prevV) * 100) : 0;
            $stage['drop'] = $drop;
            $stage['dropLabel'] = $i > 0 ? "-{$drop}%" : '—';
            $stage['dropClass'] = $i > 0 ? ($drop > 20 ? 'text-error font-medium' : 'text-base-content/40') : 'text-base-content/60';
            $stage['width'] = max(2, ($stage['v'] / $maxV) * 100);
            $stage['barTextClass'] = $stage['v'] > 0 ? 'text-white drop-shadow-sm' : 'text-base-content/40';
        }
        unset($stage);

        $this->pipelineStages = $stages;
        $this->pipelineMaxV = $maxV;
        $this->pipelineThroughput = $totalSt > 0 ? (int) round(($completed / $totalSt) * 100) : 0;
        $this->throughputClass = $totalSt > 0 ? 'text-success' : 'text-base-content/60';
        $this->pipelineAbsorption = $totalSt > 0 ? (int) round(($placed / $totalSt) * 100) : 0;
        $this->pipelineCompletionRate = $placed > 0 ? (int) round(($completed / $placed) * 100) : 0;
        $this->pipelineBottleneck = $registered > $placed ? (int) round((($registered - $placed) / $registered) * 100) : 0;
        $this->bottleneckClass = $this->pipelineBottleneck > 20 ? 'text-error' : 'text-base-content';
    }

    private function prepareFunnelMetrics(): void
    {
        $regT = max($this->stats['registrationsTotal'] ?? 0, 1);
        $this->registrationFunnel = [
            ['l' => __('dashboard.funnel.total'), 'v' => $this->stats['registrationsTotal'] ?? 0, 'p' => 100, 'c' => 'bg-base-content/20'],
            ['l' => __('dashboard.funnel.active'), 'v' => $this->stats['registrationsActive'] ?? 0, 'p' => (int) round((($this->stats['registrationsActive'] ?? 0) / $regT) * 100), 'c' => 'bg-info'],
            ['l' => __('dashboard.funnel.completed'), 'v' => $this->stats['registrationsCompleted'] ?? 0, 'p' => (int) round((($this->stats['registrationsCompleted'] ?? 0) / $regT) * 100), 'c' => 'bg-success'],
        ];

        $attD = max(($this->stats['attendanceVerified'] ?? 0) + ($this->stats['attendanceUnverified'] ?? 0), 1);
        $logD = max(($this->stats['logbookVerified'] ?? 0) + ($this->stats['logbookPending'] ?? 0), 1);
        $this->activityMetrics = [
            'attD' => $attD,
            'attP' => (int) round((($this->stats['attendanceVerified'] ?? 0) / $attD) * 100),
            'logD' => $logD,
            'logP' => (int) round((($this->stats['logbookVerified'] ?? 0) / $logD) * 100),
        ];

        $capD = max($this->stats['placementCapacity'] ?? 0, 1);
        $certTotal = max($this->stats['certificatesTotal'] ?? 0, 1);
        $this->completionMetrics = [
            'fillP' => (int) round((($this->stats['placementFilled'] ?? 0) / $capD) * 100),
            'certP' => ($this->stats['certificatesTotal'] ?? 0) > 0 ? (int) round((($this->stats['certificatesIssued'] ?? 0) / $this->stats['certificatesTotal']) * 100) : 0,
            'certTotal' => $certTotal,
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
            Cache::store()->put(config('cache-keys.health_check'), true, 1);
            $val = Cache::store()->get(config('cache-keys.health_check'));

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
