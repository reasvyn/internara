<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Evaluation\EvaluateMentorAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Gate;

class MentorController extends Controller
{
    public function evaluate(User $mentor, EvaluateMentorAction $action)
    {
        Gate::authorize('evaluateMentor', $mentor);

        $action->execute(request()->user(), $mentor, []);

        return back()->with('success', 'Mentor evaluation submitted.');
    }
}
