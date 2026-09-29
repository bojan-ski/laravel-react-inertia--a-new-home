<?php

namespace App\Http\Requests;

use App\Enums\Amenity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
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
            ...self::filterRules(),
            'sort' => ['nullable', 'string', Rule::in(self::SORTS)],
        ];
    }

    /**
     * The filter rules without sort. They read the data under validation rather
     * than the request, so stored filters can be re-validated with Validator::make.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function filterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'listingType' => ['nullable', 'string', Rule::in(['sale', 'rent'])],
            'propertyType' => ['nullable', 'string', Rule::in(self::PROPERTY_TYPES)],
            'city' => ['nullable', 'string', 'max:255'],
            'minPrice' => ['nullable', 'numeric', 'min:0'],
            'maxPrice' => ['nullable', 'numeric', 'min:0', Rule::when(fn (Fluent $input): bool => filled($input->get('minPrice')), 'gte:minPrice')],
            'minBedrooms' => ['nullable', 'integer', 'min:1', 'max:10'],
            'minBathrooms' => ['nullable', 'integer', 'min:1', 'max:10'],
            'minArea' => ['nullable', 'numeric', 'min:0'],
            'maxArea' => ['nullable', 'numeric', 'min:0', Rule::when(fn (Fluent $input): bool => filled($input->get('minArea')), 'gte:minArea')],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['distinct', Rule::enum(Amenity::class)],
        ];
    }

    /**
     * The normalized, trimmed filter state shared with the frontend and service.
     *
     * @return array{search: string, listingType: string, propertyType: string, city: string, minPrice: string, maxPrice: string, minBedrooms: string, minBathrooms: string, minArea: string, maxArea: string, amenities: list<string>, sort: string}
     */
    public function filters(): array
    {
        return self::normalizeFilters($this->all());
    }

    /**
     * The full filter shape with every filter unset.
     *
     * @return array{search: string, listingType: string, propertyType: string, city: string, minPrice: string, maxPrice: string, minBedrooms: string, minBathrooms: string, minArea: string, maxArea: string, amenities: list<string>, sort: string}
     */
    public static function emptyFilters(): array
    {
        return [
            'search' => '',
            'listingType' => '',
            'propertyType' => '',
            'city' => '',
            'minPrice' => '',
            'maxPrice' => '',
            'minBedrooms' => '',
            'minBathrooms' => '',
            'minArea' => '',
            'maxArea' => '',
            'amenities' => [],
            'sort' => 'newest',
        ];
    }

    /**
     * Normalize raw filter input over the empty shape: trimmed strings, and
     * known amenities only, de-duplicated in enum order.
     *
     * @param  array<string, mixed>  $input
     * @return array{search: string, listingType: string, propertyType: string, city: string, minPrice: string, maxPrice: string, minBedrooms: string, minBathrooms: string, minArea: string, maxArea: string, amenities: list<string>, sort: string}
     */
    public static function normalizeFilters(array $input): array
    {
        $filters = self::emptyFilters();

        foreach ($filters as $key => $default) {
            if ($key === 'amenities') {
                continue;
            }

            $value = $input[$key] ?? null;
            $value = is_scalar($value) ? trim((string) $value) : '';

            $filters[$key] = $value !== '' ? $value : $default;
        }

        $filters['amenities'] = collect(Amenity::cases())
            ->map(fn (Amenity $amenity): string => $amenity->value)
            ->intersect((array) ($input['amenities'] ?? []))
            ->values()
            ->all();

        return $filters;
    }
}
