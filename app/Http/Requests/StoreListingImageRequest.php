<?php

namespace App\Http\Requests;

use App\Models\Listing;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreListingImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $listing = $this->route('listing');

        return $listing instanceof Listing
            && ($this->user()?->can('update', $listing) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
                Rule::dimensions()->maxWidth(Listing::MAX_IMAGE_SIDE)->maxHeight(Listing::MAX_IMAGE_SIDE),
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $listing = $this->route('listing');

                if ($listing instanceof Listing && $listing->images()->count() >= Listing::MAX_IMAGES) {
                    $validator->errors()->add('image', __('app.listing_images.validation.limit', ['max' => Listing::MAX_IMAGES]));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.dimensions' => __('app.listing_images.validation.dimensions'),
            'image.image' => __('app.listing_images.validation.type'),
            'image.mimes' => __('app.listing_images.validation.type'),
            'image.max' => __('app.listing_images.validation.size'),
            'image.uploaded' => __('app.listing_images.validation.upload_failed'),
        ];
    }
}
