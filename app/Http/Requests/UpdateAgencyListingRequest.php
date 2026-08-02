<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesListingFields;
use App\Models\Listing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAgencyListingRequest extends FormRequest
{
    use ValidatesListingFields;

    public function authorize(): bool
    {
        $listing = $this->route('listing');

        return $listing instanceof Listing
            && ($this->user()?->can('update', $listing) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->sharedListingRules();
    }
}
