declare(strict_types=1);

namespace App\Http\Requests\Assignment;

use App\Enums\AssignmentType;
use App\Domain\Assignment\Models\Assignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Form Request for creating assignment.
 *
 * S1 - Secure: Validates assignment creation at HTTP layer.
 */
class CreateAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Assignment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'string', new Enum(AssignmentType::class)],
            'due_date' => ['required', 'date', 'after:today'],
            'max_score' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'internship_id' => ['required', 'uuid', 'exists:internships,id'],
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
            'due_date.after' => 'The due date must be in the future.',
            'internship_id.exists' => 'The selected internship does not exist.',
        ];
    }
}
