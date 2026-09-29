<?php

namespace App\Http\Requests;

use App\Enums\Amenity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListingIndexRequest extends FormRequest
{
    private const PROPERTY_TYPES = [
        'house', 'flat', 'apartment', 'cottage', 'villa',
        'condo', 'land', 'commercial', 'other',
    ];

    private const SORTS = [
        'newest', 'oldest', 'price_low_high', 'price_high_low',
    ];

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
            'search' => ['nullable', 'string', 'max:255'],
            'listingType' => ['nullable', 'string', Rule::in(['sale', 'rent'])],
            'propertyType' => ['nullable', 'string', Rule::in(self::PROPERTY_TYPES)],
            'city' => ['nullable', 'string', 'max:255'],
            'minPrice' => ['nullable', 'numeric', 'min:0'],
            'maxPrice' => ['nullable', 'numeric', 'min:0', Rule::when($this->filled('minPrice'), 'gte:minPrice')],
            'minBedrooms' => ['nullable', 'integer', 'min:1', 'max:10'],
            'minBathrooms' => ['nullable', 'integer', 'min:1', 'max:10'],
            'minArea' => ['nullable', 'numeric', 'min:0'],
            'maxArea' => ['nullable', 'numeric', 'min:0', Rule::when($this->filled('minArea'), 'gte:minArea')],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['distinct', Rule::enum(Amenity::class)],
            'sort' => ['nullable', 'string', Rule::in(self::SORTS)],
        ];
    }

    /**
     * The normalized, trimmed filter state shared with the frontend and service.
     *
     * @return array{search: string, listingType: string, propertyType: string, city: string, minPrice: string, maxPrice: string, minBedrooms: string, minBathrooms: string, minArea: string, maxArea: string, amenities: list<string>, sort: string}
     */
    public function filters(): array
    {
        $sort = $this->string('sort')->trim()->toString();

        return [
            'search' => $this->string('search')->trim()->toString(),
            'listingType' => $this->string('listingType')->trim()->toString(),
            'propertyType' => $this->string('propertyType')->trim()->toString(),
            'city' => $this->string('city')->trim()->toString(),
            'minPrice' => $this->string('minPrice')->trim()->toString(),
            'maxPrice' => $this->string('maxPrice')->trim()->toString(),
            'minBedrooms' => $this->string('minBedrooms')->trim()->toString(),
            'minBathrooms' => $this->string('minBathrooms')->trim()->toString(),
            'minArea' => $this->string('minArea')->trim()->toString(),
            'maxArea' => $this->string('maxArea')->trim()->toString(),
            'amenities' => collect(Amenity::cases())
                ->map(fn (Amenity $amenity): string => $amenity->value)
                ->intersect((array) $this->input('amenities', []))
                ->values()
                ->all(),
            'sort' => $sort !== '' ? $sort : 'newest',
        ];
    }
}
