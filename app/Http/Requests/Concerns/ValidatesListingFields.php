<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Amenity;
use App\Models\Listing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ValidatesListingFields
{
    private const PROPERTY_TYPES = [
        'house', 'flat', 'apartment', 'cottage', 'villa',
        'condo', 'land', 'commercial', 'other',
    ];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function sharedListingRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'listing_type' => ['required', 'string', Rule::in(['sale', 'rent'])],
            'property_type' => ['required', 'string', Rule::in(self::PROPERTY_TYPES)],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:10000'],
            'country' => ['required', 'string', Rule::in(Listing::countryOptions())],
            'city' => ['required', 'string', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'area_value' => ['nullable', 'numeric', 'min:0'],
            'year_built' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'distinct', Rule::enum(Amenity::class)],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function newImageRules(): array
    {
        return [
            'images' => ['nullable', 'array', 'max:'.Listing::MAX_NEW_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
