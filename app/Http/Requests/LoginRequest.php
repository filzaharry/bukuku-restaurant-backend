<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => 'required|string',
            'email' => 'required|email',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'email wajib diisi.',
            'email.string' => 'email harus berupa string.',
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa string.'
        ];
    }
}
