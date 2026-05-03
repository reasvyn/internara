declare(strict_types=1);

namespace App\Http\Requests\Internship;

use App\Enums\InternshipStatus;
use App\Domain\Internship\Models\Internship;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Form Request for creating internships.
 *
 * S1 - Secure: Centralizes validation logic at the HTTP layer.
 * Validated data can be safely passed to Actions.
 */
class CreateInternshipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Internship::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['required', 'date', 'after:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['sometimes', 'string', new Enum(InternshipStatus::class)],
            'company_id' => ['required', 'uuid', 'exists:internship_companies,id'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.after' => 'The start date must be in the future.',
            'end_date.after' => 'The end date must be after the start date.',
            'company_id.exists' => 'The selected company does not exist.',
            'department_id.exists' => 'The selected department does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_id' => 'company',
            'department_id' => 'department',
        ];
    }
}
