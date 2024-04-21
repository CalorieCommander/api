<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class RegisterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ];
    }
    public function messages(): array
    {
        return [
            'first_name.required' => 'Voornaam is verplicht.',
            'first_name.max' => 'Voornaam mag niet langer zijn dan 255 karakters.',
            'last_name.required' => 'Achternaam is verplicht.',
            'last_name.min' => 'Achternaam mag maximaal 255 karakters bevatten.',
            'email.required' => 'Email is verplicht.',
            'email.email' => 'Dit is geen geldige email.',
            'email.max' => 'Email max maximaal 255 karakters bevatten.',
            'email.unique' => 'Deze email is al in gebruik.',
            'password.required' => 'Wachtwoord is verplicht',
            'password.min' => 'Wachtwoord moet minimaal 6 karakters bevatten.',
            'password.confirmed' => 'Wachtwoorden zijn niet gelijk.',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors(),
            'status' => true
        ], 422));
    }
}
