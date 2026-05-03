
declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Rules\SystemUsername;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Setup the first Super Admin account.
 */
class SetupSuperAdminAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    /**
     * @param array{name: string, email: string, username: string, password: string} $data
     */
    public function execute(array $data): User
    {
        Validator::make($data, [
            'username' => ['required', 'string', 'unique:users,username', new SystemUsername],
            'email' => ['required', 'email', 'unique:users,email'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $user = User::create([
                ...$data,
                'password' => Hash::make($data['password']),
                'setup_required' => false,
            ]);

            // S1: Mark email as verified for the first super admin to allow immediate access
            $user->markEmailAsVerified();

            // Assign super_admin role using Spatie Permission (matches RoleEnum::SUPER_ADMIN)
            $user->assignRole('super_admin');

            $this->logAudit->execute(
                action: 'super_admin_created',
                subjectType: User::class,
                subjectId: $user->id,
                payload: ['username' => $data['username']],
                module: 'Setup',
            );

            return $user;
        });
    }
}
