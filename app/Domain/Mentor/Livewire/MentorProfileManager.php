<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Livewire;

use App\Domain\Mentor\Actions\CreateMentorProfileAction;
use App\Domain\Mentor\Actions\ToggleMentorActiveAction;
use App\Domain\Mentor\Actions\UpdateMentorProfileAction;
use App\Domain\Mentor\Models\Mentor;
use App\Domain\User\Models\User;
use Illuminate\View\View;
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

    public function store(CreateMentorProfileAction $action): void
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

        $action->execute(
            userId: $this->userId,
            type: $this->type,
            employeeId: $this->employeeId,
            companyName: $this->companyName,
            position: $this->position,
            phone: $this->phone,
            bio: $this->bio,
            specialization: $this->specialization,
        );

        $this->showForm = false;
        $this->resetForm();
        flash()->success('Mentor profile created successfully.');
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

    public function update(UpdateMentorProfileAction $action): void
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

        $action->execute(
            mentor: $this->editingMentor,
            type: $this->type,
            employeeId: $this->employeeId,
            companyName: $this->companyName,
            position: $this->position,
            phone: $this->phone,
            bio: $this->bio,
            specialization: $this->specialization,
        );

        $this->showForm = false;
        $this->resetForm();
        flash()->success('Mentor profile updated successfully.');
    }

    public function toggleStatus(Mentor $mentor, ToggleMentorActiveAction $action): void
    {
        $action->execute($mentor);
        flash()->success('Mentor status updated.');
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
    public function render(): View
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

        return view('mentor.profile-manager', [
            'mentors' => $mentors,
            'usersWithoutMentorProfile' => $usersWithoutMentorProfile,
        ]);
    }
}
