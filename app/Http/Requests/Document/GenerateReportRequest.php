
declare(strict_types=1);

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_type' => [
                'required',
                'string',
                'in:attendance_summary,internship_placements,student_performance,company_overview',
            ],
            'filters' => ['nullable', 'array'],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date', 'after_or_equal:filters.date_from'],
            'filters.department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'filters.company_id' => ['nullable', 'uuid', 'exists:companies,id'],
        ];
    }
}
