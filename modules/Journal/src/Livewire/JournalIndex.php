<?php

declare(strict_types=1);

namespace Modules\Journal\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Journal\Services\Contracts\JournalService;
use Modules\User\Models\User;

class JournalIndex extends Component
{
    use WithPagination;

    public string $search = '';

    /**
     * Get the journals based on user role.
     */
    public function getJournalsProperty()
    {
        /** @var JournalService $service */
        $service = app(JournalService::class);
        $user = auth()->user();
        $filters = ['search' => $this->search, 'sort_by' => 'date', 'sort_dir' => 'desc'];

        if ($user->hasRole('student')) {
            $filters['student_id'] = $user->id;
        } elseif ($user->hasRole(['teacher', 'mentor'])) {
            // For supervisors, we filter via registration relationship
            // This requires a more complex query than simple EloquentQuery filter
            $query = $service->query($filters);
            $query->whereHas('registration', function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                  ->orWhere('mentor_id', $user->id);
            });
            return $query->paginate(10);
        }

        return $service->paginate($filters, 10);
    }

    public function render(): View
    {
        return view('journal::livewire.journal-index')->layout('dashboard::components.layouts.dashboard', [
            'title' => __('Jurnal Harian'),
        ]);
    }
}