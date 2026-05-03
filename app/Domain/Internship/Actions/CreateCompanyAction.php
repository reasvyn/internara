declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Internship\Models\Company;
use Illuminate\Support\Facades\DB;

class CreateCompanyAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            $company = Company::create($data);

            $this->logAudit->execute(
                action: 'company_created',
                subjectType: Company::class,
                subjectId: $company->id,
                payload: ['name' => $company->name],
                module: 'Company',
            );

            return $company;
        });
    }
}
