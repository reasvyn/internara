<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Schedule\Actions\CreateScheduleAction;
use App\Domain\Schedule\Actions\DeleteScheduleAction;
use App\Domain\Schedule\Actions\UpdateScheduleAction;
use App\Domain\Schedule\Models\Schedule;
use App\Http\Requests\CreateScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Schedule::class);

        $schedules = Schedule::with('creator')->latest('start_at')->paginate(20);

        return view('livewire.admin.schedules.index', [
            'schedules' => $schedules,
        ]);
    }

    public function store(CreateScheduleRequest $request, CreateScheduleAction $action)
    {
        Gate::authorize('create', Schedule::class);

        $action->execute($request->user(), $request->validated());

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Schedule created successfully.');
    }

    public function update(
        UpdateScheduleRequest $request,
        Schedule $schedule,
        UpdateScheduleAction $action,
    ) {
        Gate::authorize('update', $schedule);

        $action->execute($request->user(), $schedule, $request->validated());

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Schedule updated successfully.');
    }

    public function destroy(Schedule $schedule, Request $request, DeleteScheduleAction $action)
    {
        Gate::authorize('delete', $schedule);

        $action->execute($request->user(), $schedule);

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Schedule deleted successfully.');
    }
}
