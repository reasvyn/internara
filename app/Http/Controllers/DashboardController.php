<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Setup\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Redirect the user to their appropriate dashboard based on their role.
     */
    public function __invoke(Request $request, OnboardingService $onboarding): RedirectResponse
    {
        $user = $request->user();

        // Check for pending onboarding steps
        if ($user->hasAnyRole(['super_admin', 'admin']) && $onboarding->getNextStep() !== null) {
            // In a real app, you might redirect to a specific onboarding route 
            // or pass a flag to the dashboard Livewire component.
            // For now, we'll just ensure the service is available for injection in components.
        }

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('student')) {
            return redirect()->route('student.dashboard');
        }

        if ($user->hasRole('teacher')) {
            return redirect()->route('teacher.dashboard');
        }

        if ($user->hasRole('mentor')) {
            return redirect()->route('mentor.dashboard');
        }

        return redirect()->route('home');
    }
}
