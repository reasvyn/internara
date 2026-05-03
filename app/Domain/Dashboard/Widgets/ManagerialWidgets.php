
declare(strict_types=1);

namespace App\Domain\Dashboard\Widgets;

use App\Domain\Dashboard\Actions\GetManagerialStatsAction;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ManagerialWidgets extends Component
{
    /**
     * Get statistics for the dashboard.
     */
    #[Computed]
    public function stats(): array
    {
        return app(GetManagerialStatsAction::class)->execute();
    }

    public function render()
    {
        return <<<'HTML'
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-mary-stat
                title="Total Students"
                value="{{ $this->stats['users']['students'] }}"
                icon="o-academic-cap"
                color="text-primary" />

            <x-mary-stat
                title="Active Internships"
                value="{{ $this->stats['internships']['active'] }}"
                icon="o-briefcase"
                color="text-success" />

            <x-mary-stat
                title="Placement Rate"
                value="{{ $this->stats['internships']['placement_rate'] }}%"
                icon="o-chart-pie"
                color="text-info" />

            <x-mary-stat
                title="Today's Attendance"
                value="{{ $this->stats['attendance']['today_present'] }}"
                icon="o-check-circle"
                color="text-warning" />
        </div>
        HTML;
    }
}
