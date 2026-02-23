<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class JobMonitor extends Component
{
    use WithPagination;

    public string $tab = 'pending';

    /**
     * Set the active tab.
     */
    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    /**
     * Retry a failed job.
     */
    public function retry(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => $uuid]);
        flash()->success(__('admin::ui.job_retried'));
    }

    /**
     * Delete a failed job.
     */
    public function forget(string $uuid): void
    {
        Artisan::call('queue:forget', ['id' => $uuid]);
        flash()->warning(__('admin::ui.job_forgotten'));
    }

    /**
     * Flush all failed jobs.
     */
    public function flush(): void
    {
        Artisan::call('queue:flush');
        flash()->success(__('admin::ui.all_failed_jobs_flushed'));
    }

    public function render()
    {
        $pendingJobs = [];
        $failedJobs = [];

        if ($this->tab === 'pending') {
            $pendingJobs = DB::table('jobs')->paginate(10);
        } else {
            $failedJobs = DB::table('failed_jobs')->paginate(10);
        }

        return view('admin::livewire.job-monitor', [
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('admin::ui.menu.job_monitor') . ' | ' . setting('brand_name', setting('app_name')),
            'context' => 'admin::ui.menu.job_monitor',
        ]);
    }
}
