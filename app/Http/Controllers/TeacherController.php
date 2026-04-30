<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeacherController extends Controller
{
    public function dashboard(Request $request)
    {
        Gate::authorize('viewTeacherDashboard', User::class);

        return view('livewire.teacher.dashboard');
    }

    public function assessInternship(Request $request)
    {
        Gate::authorize('assessInternship', User::class);

        return view('livewire.teacher.assess-internship');
    }
}
