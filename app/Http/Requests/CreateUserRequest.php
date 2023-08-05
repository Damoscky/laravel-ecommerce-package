<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|unique:users,email',
            'phoneno' => 'required|unique:users,email'
        ];
    }


    public function messages(): array
    {
        return [
            'firstname.required' => 'The Firstname is required',
            'lastname.required' => 'The Lastname is required',
            'email.required' => 'The Email address is required',
            'email.unique' => 'The Email address already exist',
            'phoneno.required' => 'The Phone Number is required',
            'phoneno.unique' => 'The Phone Number already exist',
        ];
    }
}
