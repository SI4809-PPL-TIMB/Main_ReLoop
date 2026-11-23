<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 8.1.2 · Admin menambah/mengubah mitra beserta akun pengepulnya. */
class MitraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi middleware peran:admin di route
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'telepon' => preg_replace('/[\s\-]/', '', (string) $this->input('telepon')),
            'email'   => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        $mitra = $this->route('mitra');

        return [
            'nama_usaha'   => ['required', 'string', 'max:150'],
            'name'         => ['required', 'string', 'max:150'],
            'email'        => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($mitra?->user_id)],
            'password'     => [$mitra ? 'nullable' : 'required', 'string', 'min:8', 'max:1024'],
            'telepon'      => ['required', 'regex:/^(\+62|62|0)8\d{7,11}$/'],
            'alamat'       => ['required', 'string', 'max:255'],
            'kelurahan_id' => ['required', 'integer', Rule::exists('wilayah_kelurahan', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_usaha.required'   => 'Nama usaha wajib diisi.',
            'name.required'         => 'Nama pemilik wajib diisi.',
            'email.required'        => 'Email akun pengepul wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email ini sudah dipakai akun lain.',
            'password.required'     => 'Kata sandi awal wajib diisi.',
            'password.min'          => 'Kata sandi minimal 8 karakter.',
            'telepon.required'      => 'Nomor HP wajib diisi.',
            'telepon.regex'         => 'Nomor HP tidak valid, mis. 081234567890.',
            'alamat.required'       => 'Alamat lapak wajib diisi.',
            'kelurahan_id.required' => 'Pilih kelurahan lapak.',
            'kelurahan_id.exists'   => 'Kelurahan tidak ditemukan.',
        ];
    }
}
