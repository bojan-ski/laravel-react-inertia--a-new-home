<?php

namespace App\Http\Requests\Admin;

use App\Models\ListingReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class TakeDownListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('takeDown', [ListingReport::class, $this->route('listing')]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
