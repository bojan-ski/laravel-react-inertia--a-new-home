<?php

namespace App\Http\Requests;

use App\Enums\EuropeanCountry;
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
     * Uppercase the VAT ID and strip its spaces so "de 123 456 789" is stored as "DE123456789".
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('vat_id'))) {
            $this->merge([
                'vat_id' => mb_strtoupper(preg_replace('/\s+/', '', $this->input('vat_id'))),
            ]);
        }
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
            'vat_id' => ['nullable', 'string', 'regex:/^[A-Z]{2}[A-Z0-9]{2,13}$/'],
            'registration_number' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\/.\- ]+$/'],
            'registration_country' => ['required', Rule::enum(EuropeanCountry::class)],
            'broker_register_number' => ['nullable', 'string', 'max:50'],
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
            'vat_id.regex' => __('app.agency_business.validation.vat_id'),
            'registration_number.regex' => __('app.agency_business.validation.registration_number'),
        ];
    }
}
