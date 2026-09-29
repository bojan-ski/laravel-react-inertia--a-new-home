<?php

namespace App\Http\Requests;

use App\Models\SavedSearch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;

class StoreSavedSearchRequest extends ListingIndexRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', SavedSearch::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => ['nullable', 'string', 'max:100'],
            'alerts_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
