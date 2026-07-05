<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReportedChatMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The participant, self-report, and duplicate rules are enforced by
     * ReportedChatMessageService; any authenticated user may attempt a report.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
