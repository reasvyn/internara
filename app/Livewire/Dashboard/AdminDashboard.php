
declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Domain\Internship\Models\Internship;
use App\Domain\School\Models\Department;
use App\Domain\User\Models\User;
use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Services\SystemAuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    public int $totalStudents = 0;

    public int $totalTeachers = 0;

    public int $totalDepartments = 0;

    public int $activeInternships = 0;

    public array $readiness = [];

    public function mount(SystemAuditService $auditService): void
    {
        $this->totalStudents = User::role(RoleEnum::STUDENT->value)->count();
        $this->totalTeachers = User::role(RoleEnum::TEACHER->value)->count();
        $this->totalDepartments = Department::count();
        $this->activeInternships = Internship::where('status', 'active')->count();

        // Map old structure to view requirements if SystemAuditService was modified.
        // Assuming $auditService->run() returns array of checks.
        $results = $auditService->run();
        $this->readiness = [
            'database' => ['label' => 'Database Connection', 'passed' => $results['database'] ?? true],
            'mail' => ['label' => 'Mail Configuration', 'passed' => $results['mail'] ?? true],
            'cache' => ['label' => 'Cache System', 'passed' => $results['cache'] ?? true],
            'queue' => ['label' => 'Queue Worker', 'passed' => $results['queue'] ?? true],
            'storage' => ['label' => 'Storage Link', 'passed' => $results['storage'] ?? true],
        ];
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.dashboard.admin', [
            'stats' => [
                'students' => $this->totalStudents,
                'teachers' => $this->totalTeachers,
                'departments' => $this->totalDepartments,
                'internships' => $this->activeInternships,
            ],
        ]);
    }
}
