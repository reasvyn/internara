<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\Department;
use App\Models\Internship;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

class AdminDashboard extends Component
{
    public int $totalStudents = 0;

    public int $totalTeachers = 0;

    public int $totalDepartments = 0;

    public int $activeInternships = 0;

    public function mount(): void
    {
        $this->totalStudents = User::role(RoleEnum::STUDENT->value)->count();
        $this->totalTeachers = User::role(RoleEnum::TEACHER->value)->count();
        $this->totalDepartments = Department::count();
        $this->activeInternships = Internship::where('status', 'active')->count();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.admin-dashboard');
    }
}
