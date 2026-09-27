<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesListingFields;
use App\Models\Listing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgencyListingRequest extends FormRequest
{
    use ValidatesListingFields;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Listing::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->sharedListingRules(),
            ...$this->newImageRules(),
        ];
    }
}
