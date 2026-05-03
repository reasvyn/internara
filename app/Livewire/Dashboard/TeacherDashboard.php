declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.dashboard.teacher');
    }
}
