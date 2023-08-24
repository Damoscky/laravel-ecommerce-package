<?php

namespace SbscPackage\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|exists:users,email',
            'password' => 'required'
        ];
    }


    public function messages(): array
    {
        return [
            'email.required' => 'The Email address is required',
            'email.exists' => 'The Email address do not exist',
            'password.required' => 'The Password is required',
        ];
    }
}
