<?php

namespace App\Http\Requests;

use App\Enums\UsState;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgencyProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'legal_name' => ['required', 'string', 'max:255'],
            'ein' => ['required', 'string', 'regex:/^\d{2}-\d{7}$/'],
            'license_number' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9-]+$/'],
            'license_state' => ['required', Rule::enum(UsState::class)],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ein.regex' => __('app.agency_business.validation.ein'),
            'license_number.regex' => __('app.agency_business.validation.license_number'),
        ];
    }
}
