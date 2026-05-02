<?php

declare(strict_types=1);

namespace App\Services\Setup;

use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * Manages the onboarding process for newly setup users.
 *
 * S2 - Sustain: Guides users through initial setup steps.
 */
class OnboardingService
{
    private const SESSION_KEY = 'onboarding.completed_steps';

    /**
     * Essential onboarding steps for new administrators.
     */
    public const ADMIN_STEPS = [
        'welcome' => [
            'title' => 'Welcome to Internara',
            'description' => 'Let\'s get your internship management started.',
        ],
        'profile' => [
            'title' => 'Complete Your Profile',
            'description' => 'Add your photo and contact details.',
        ],
        'settings' => [
            'title' => 'Configure System',
            'description' => 'Review branding and notification settings.',
        ],
    ];

    /**
     * Mark an onboarding step as completed.
     */
    public function completeStep(string $step): void
    {
        $completed = $this->getCompletedSteps();
        if (!in_array($step, $completed)) {
            $completed[] = $step;
            Session::put(self::SESSION_KEY, $completed);
        }
    }

    /**
     * Get all completed onboarding steps.
     *
     * @return array<int, string>
     */
    public function getCompletedSteps(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * Check if a specific step is completed.
     */
    public function isStepCompleted(string $step): bool
    {
        return in_array($step, $this->getCompletedSteps());
    }

    /**
     * Get the next pending step for the user.
     */
    public function getNextStep(): ?string
    {
        foreach (array_keys(self::ADMIN_STEPS) as $step) {
            if (!$this->isStepCompleted($step)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Clear onboarding state.
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
