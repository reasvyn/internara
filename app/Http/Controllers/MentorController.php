<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Mentor\EvaluateMentorAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MentorController extends Controller
{
    public function dashboard(Request $request)
    {
        Gate::authorize('viewMentorDashboard', User::class);

        return view('livewire.mentor.dashboard');
    }

    public function evaluate(User $mentor, EvaluateMentorAction $action)
    {
        Gate::authorize('evaluateMentor', $mentor);

        $action->execute($request->user(), $mentor, []);

        return back()->with('success', 'Mentor evaluation submitted.');
    }
}
