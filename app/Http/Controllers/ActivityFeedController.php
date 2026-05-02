<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ActivityFeedController extends Controller
{
    public function index(Request $request)
    {
        $activities = $request->user()->activityLogs()->latest()->paginate(50);

        return view('livewire.admin.activities.index', [
            'activities' => $activities,
        ]);
    }
}
