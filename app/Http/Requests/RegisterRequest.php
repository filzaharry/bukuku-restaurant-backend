<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all users to register
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => 'required|string|max:255',
            'fullname' => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6',
        ];
    }

    /**
     * Get custom error messages for validator.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'fullname.required' => 'Fullname is required.',
            'fullname.string'   => 'Fullname must be a string.',
            'fullname.max'      => 'Fullname may not be greater than 255 characters.',
            'email.required'    => 'Email is required.',
            'email.email'       => 'Email must be a valid email address.',
            'email.unique'      => 'Email has already been taken.',
            'phone.required'    => 'Phone is required.',
            'phone.string'      => 'Phone must be a string.',
            'phone.max'         => 'Phone may not be greater than 20 characters.',
            'phone.unique'      => 'Phone has already been taken.',
            'password.required' => 'Password is required.',
            'password.string'   => 'Password must be a string.',
            'password.min'      => 'Password must be at least 6 characters.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $flattenedErrors = collect($validator->errors())->flatten()->all();
        $traceId = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        throw new HttpResponseException(response()->json([
            'statusCode' => 422,
            'message' => 'Validation error',
            'errors' => $flattenedErrors,
            'traceId' => [$traceId]
        ], 422));
    }
}
