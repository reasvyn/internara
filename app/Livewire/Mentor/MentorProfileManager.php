<?php

declare(strict_types=1);

namespace App\Livewire\Mentor;

use App\Models\Mentor\Mentor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class MentorProfileManager extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?Mentor $editingMentor = null;

    public string $userId = '';

    public string $type = '';

    public ?string $employeeId = null;

    public ?string $companyName = null;

    public ?string $position = '';

    public ?string $phone = '';

    public ?string $bio = '';

    public ?string $specialization = '';

    public string $filterType = '';

    public function mount(): void
    {
        $this->filterType = '';
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function store(): void
    {
        $this->validate([
            'userId' => 'required|exists:users,id',
            'type' => 'required|in:school_teacher,industry_supervisor',
            'employeeId' => 'nullable|string|max:50',
            'companyName' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'bio' => 'nullable|string|max:2000',
            'specialization' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () {
            $user = User::findOrFail($this->userId);
            $user->assignRole('supervisor');

            Mentor::create([
                'id' => Str::uuid(),
                'user_id' => $this->userId,
                'type' => $this->type,
                'employee_id' => $this->employeeId,
                'company_name' => $this->companyName,
                'position' => $this->position,
                'phone' => $this->phone,
                'bio' => $this->bio,
                'specialization' => $this->specialization,
                'is_active' => true,
            ]);
        });

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Mentor profile created successfully.');
    }

    public function edit(Mentor $mentor): void
    {
        $this->editingMentor = $mentor;
        $this->userId = $mentor->user_id;
        $this->type = $mentor->type;
        $this->employeeId = $mentor->employee_id;
        $this->companyName = $mentor->company_name;
        $this->position = $mentor->position;
        $this->phone = $mentor->phone;
        $this->bio = $mentor->bio;
        $this->specialization = $mentor->specialization;
        $this->showForm = true;
    }

    public function update(): void
    {
        if (! $this->editingMentor) {
            return;
        }

        $this->validate([
            'userId' => 'required|exists:users,id',
            'type' => 'required|in:school_teacher,industry_supervisor',
            'employeeId' => 'nullable|string|max:50',
            'companyName' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'bio' => 'nullable|string|max:2000',
            'specialization' => 'nullable|string|max:1000',
        ]);

        $this->editingMentor->update([
            'type' => $this->type,
            'employee_id' => $this->employeeId,
            'company_name' => $this->companyName,
            'position' => $this->position,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'specialization' => $this->specialization,
        ]);

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Mentor profile updated successfully.');
    }

    public function toggleStatus(Mentor $mentor): void
    {
        $mentor->update(['is_active' => ! $mentor->is_active]);
        $this->dispatch('notify', type: 'success', message: 'Mentor status updated.');
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->userId = '';
        $this->type = '';
        $this->employeeId = null;
        $this->companyName = null;
        $this->position = '';
        $this->phone = '';
        $this->bio = '';
        $this->specialization = '';
        $this->editingMentor = null;
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $query = Mentor::query()->with('user');

        if ($this->filterType) {
            $query->ofType($this->filterType);
        }

        $mentors = $query->latest()->paginate(10);

        $usersWithoutMentorProfile = User::role('supervisor')
            ->whereDoesntHave('mentor')
            ->orderBy('name')
            ->get();

        return view('livewire.mentor.mentor-profile-manager', [
            'mentors' => $mentors,
            'usersWithoutMentorProfile' => $usersWithoutMentorProfile,
        ]);
    }
}
