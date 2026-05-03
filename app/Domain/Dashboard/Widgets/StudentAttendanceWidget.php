declare(strict_types=1);

namespace App\Domain\Dashboard\Widgets;

use App\Domain\Attendance\Actions\ClockInAction;
use App\Domain\Attendance\Actions\ClockOutAction;
use App\Domain\Attendance\Models\AttendanceLog;
use Carbon\Carbon;
use Livewire\Component;
use Mary\Traits\Toast;

class AttendanceWidget extends Component
{
    use Toast;

    public ?AttendanceLog $todayLog = null;

    public string $currentTime = '';

    public function mount(): void
    {
        $this->loadTodayLog();
        $this->currentTime = Carbon::now()->format('H:i:s');
    }

    public function loadTodayLog(): void
    {
        $this->todayLog = AttendanceLog::where('user_id', auth()->id())
            ->where('date', Carbon::today()->toDateString())
            ->first();
    }

    public function clockIn(ClockInAction $clockInAction): void
    {
        try {
            // In a real app, we'd get lat/long from JS
            $clockInAction->execute(auth()->user(), [
                'ip' => request()->ip(),
            ]);

            $this->loadTodayLog();
            $this->success('Clocked in successfully!');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function clockOut(ClockOutAction $clockOutAction): void
    {
        try {
            $clockOutAction->execute(auth()->user(), [
                'ip' => request()->ip(),
            ]);

            $this->loadTodayLog();
            $this->success('Clocked out successfully!');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.student.attendance-widget');
    }
}
