<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:150'],
            'identifier' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8'],
            'terms'      => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Nama lengkap wajib diisi.',
            'name.max'            => 'Nama maksimal 150 karakter.',
            'identifier.required' => 'Email wajib diisi.',
            'identifier.email'    => 'Format email tidak valid.',
            'identifier.unique'   => 'Email ini sudah terdaftar. Silakan masuk atau gunakan email lain.',
            'password.required'   => 'Kata sandi wajib diisi.',
            'password.min'        => 'Kata sandi minimal 8 karakter.',
            'terms.accepted'      => 'Kamu harus menyetujui Syarat & Ketentuan.',
        ];
    }
}