<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Admin accounts are never self-registerable; reject the attempt with a
     * 403 before validation runs. Validation still restricts account_type to
     * the two public roles for any other invalid value.
     */
    public function authorize(): bool
    {
        return $this->input('account_type') !== UserRole::Admin->value;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'account_type' => ['required', 'string', Rule::in([UserRole::RegularUser->value, UserRole::Agency->value])],
            'terms_accepted' => ['accepted'],
            'privacy_accepted' => ['accepted'],
        ];
    }
}
