declare(strict_types=1);

namespace App\Http\Requests\School;

use App\Domain\School\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for creating school.
 *
 * S1 - Secure: Validates school creation input at HTTP layer.
 */
class CreateSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', School::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'institutional_code' => [
                'required',
                'string',
                'max:50',
                'unique:schools,institutional_code',
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'principal_name' => ['required', 'string', 'max:255'],
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
            'institutional_code.unique' => 'The institutional code is already in use.',
            'institutional_code.max' => 'The institutional code must not exceed 50 characters.',
        ];
    }
}
