<?php

namespace App\Http\Requests;

use App\Models\Listing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
{
    private const PROPERTY_TYPES = [
        'house', 'flat', 'apartment', 'cottage', 'villa',
        'condo', 'land', 'commercial', 'other',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Listing::class) ?? false;
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
            'listing_type' => ['required', 'string', Rule::in(['sale', 'rent'])],
            'property_type' => ['required', 'string', Rule::in(self::PROPERTY_TYPES)],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'description' => ['required', 'string', 'max:10000'],
            'country' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'area_value' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', 'max:50'],
            'year_built' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
