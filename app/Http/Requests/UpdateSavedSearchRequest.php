<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSavedSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('savedSearch'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'alerts_enabled' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
