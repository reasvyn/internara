<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\School\Actions\ActivateAcademicYearAction;
use App\Domain\School\Actions\CreateAcademicYearAction;
use App\Domain\School\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AcademicController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', AcademicYear::class);

        $years = AcademicYear::latest('start_date')->paginate(20);

        return view('livewire.admin.academic-years.index', [
            'years' => $years,
        ]);
    }

    public function store(Request $request, CreateAcademicYearAction $action)
    {
        Gate::authorize('create', AcademicYear::class);

        $action->execute(
            $request->validate([
                'name' => ['required', 'string', 'max:50'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after:start_date'],
                'is_active' => ['nullable', 'boolean'],
            ]),
        );

        return redirect()
            ->route('admin.academic-years.index')
            ->with('success', 'Academic year created successfully.');
    }

    public function activate(AcademicYear $year, ActivateAcademicYearAction $action)
    {
        Gate::authorize('activate', $year);

        $action->execute($year);

        return back()->with('success', 'Academic year activated.');
    }
}
