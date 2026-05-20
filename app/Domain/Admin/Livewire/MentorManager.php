<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Mentor\Actions\CreateMentorAction;
use App\Domain\Mentor\Actions\DeleteMentorAction;
use App\Domain\Mentor\Actions\UpdateMentorAction;
use App\Domain\Mentor\Models\Mentor;
use Illuminate\Database\Eloquent\Builder;

class MentorManager extends BaseRecordManager
{
    public bool $userModal = false;

    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'type' => Mentor::TYPE_SCHOOL_TEACHER,
        'is_active' => true,
    ];

    public function boot(): void
    {
        if (
            ! auth()
                ->user()
                ?->hasAnyRole(['super_admin', 'admin'])
        ) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => __('user.mentor.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            ['key' => 'type', 'label' => __('user.mentor.type'), 'sortable' => true],
            ['key' => 'is_active', 'label' => __('user.mentor.active')],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Mentor::query()
            ->with('user');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('user', fn ($uq) => $uq->where('email', 'like', "%{$this->search}%"));
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($this->filters['is_active'] ?? null, fn ($q, $v) => $q->where('is_active', $v === 'yes'));
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => null,
            'name' => '',
            'email' => '',
            'type' => Mentor::TYPE_SCHOOL_TEACHER,
            'is_active' => true,
        ];
        $this->userModal = true;
    }

    public function edit(Mentor $mentor): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => $mentor->id,
            'name' => $mentor->user->name,
            'email' => $mentor->user->email,
            'type' => $mentor->type,
            'is_active' => $mentor->is_active,
        ];
        $this->userModal = true;
    }

    public function save(CreateMentorAction $createAction, UpdateMentorAction $updateAction): void
    {
        $this->validate([
            'userData.name' => 'required|string|max:255',
            'userData.email' => 'required|email|unique:users,email,'.($this->userData['id'] ? Mentor::find($this->userData['id'])?->user_id ?? 'NULL' : 'NULL'),
            'userData.type' => 'required|string|in:'.Mentor::TYPE_SCHOOL_TEACHER.','.Mentor::TYPE_INDUSTRY_SUPERVISOR,
        ]);

        if ($this->userData['id']) {
            $mentor = Mentor::with('user')->findOrFail($this->userData['id']);
            $updateAction->execute($mentor, [
                'type' => $this->userData['type'],
                'is_active' => $this->userData['is_active'],
            ]);
            flash()->success(__('user.mentor.success_updated'));
        } else {
            $createAction->execute(
                userData: [
                    'name' => $this->userData['name'],
                    'email' => $this->userData['email'],
                ],
                mentorData: [
                    'type' => $this->userData['type'],
                    'is_active' => $this->userData['is_active'],
                ],
            );
            flash()->success(__('user.mentor.success_created'));
        }

        $this->userModal = false;
    }

    public function delete(Mentor $mentor, DeleteMentorAction $deleteAction): void
    {
        $deleteAction->execute($mentor);
        flash()->success(__('user.mentor.success_deleted'));
    }

    public function deleteSelected(DeleteMentorAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $mentor = Mentor::find($id);
            if ($mentor) {
                $deleteAction->execute($mentor);
            }
        });
    }

    public function render(): View
    {
        return view('admin.mentor-manager');
    }
}
