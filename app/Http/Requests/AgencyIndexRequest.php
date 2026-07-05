<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AgencyIndexRequest extends FormRequest
{
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
        ];
    }

    /**
     * The normalized, trimmed filter state shared with the frontend and service.
     *
     * @return array{search: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->string('search')->trim()->toString(),
        ];
    }
}
