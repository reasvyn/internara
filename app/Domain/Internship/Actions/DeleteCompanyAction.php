declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Internship\Models\Company;
use Illuminate\Support\Facades\DB;

class DeleteCompanyAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Company $company): void
    {
        DB::transaction(function () use ($company) {
            $companyId = $company->id;
            $companyName = $company->name;

            $company->delete();

            $this->logAudit->execute(
                action: 'company_deleted',
                subjectType: Company::class,
                subjectId: $companyId,
                payload: ['name' => $companyName],
                module: 'Company',
            );
        });
    }
}
