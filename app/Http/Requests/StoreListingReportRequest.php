<?php

namespace App\Http\Requests;

use App\Enums\ListingReportReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreListingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $listing = $this->route('listing');

        abort_unless($listing->isActuallyPubliclyVisible(), 404);

        return Gate::allows('report', $listing);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(ListingReportReason::class)],
            'details' => [
                'nullable',
                'string',
                'max:2000',
                Rule::requiredIf($this->input('reason') === ListingReportReason::Other->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'details.required' => __('app.listing_reports.validation.details_required'),
        ];
    }
}
