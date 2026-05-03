
declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Livewire\Component;

class UserDashboard extends Component
{
    public function render()
    {
        return <<<'HTML'
        <div>
            <x-mary-header title="Dashboard" subtitle="Welcome back, {{ auth()->user()->name }}" separator />

            <livewire:dashboard.managerial-widgets />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-8">
                <div class="lg:col-span-2">
                    <x-mary-card title="Recent Activity" separator>
                        <livewire:audit.recent-activity-list />
                    </x-mary-card>
                </div>
                <div>
                    <x-mary-card title="Your Profile" separator>
                        <div class="flex items-center gap-4">
                            <x-mary-avatar :image="auth()->user()->avatar_url" class="!w-16" />
                            <div>
                                <div class="font-bold">{{ auth()->user()->name }}</div>
                                <div class="text-sm opacity-70">{{ auth()->user()->email }}</div>
                            </div>
                        </div>
                        <x-slot:actions>
                            <x-mary-button label="Edit Profile" icon="o-pencil" link="/profile" class="btn-sm" />
                        </x-slot:actions>
                    </x-mary-card>
                </div>
            </div>
        </div>
        HTML;
    }
}
