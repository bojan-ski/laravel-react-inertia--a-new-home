<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['nullable', 'string', Rule::in([UserRole::RegularUser->value, UserRole::Agency->value])],
            'status' => ['nullable', 'string', Rule::enum(UserStatus::class)],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{role: ?string, status: ?string, search: ?string}
     */
    public function filters(): array
    {
        return [
            'role' => $this->validated('role') ?: null,
            'status' => $this->validated('status') ?: null,
            'search' => $this->validated('search') ?: null,
        ];
    }
}
