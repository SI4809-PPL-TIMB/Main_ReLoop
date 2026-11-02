<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PilihTipeKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipe' => [
                'bail',
                'required',
                'string',
                Rule::in(array_keys(Category::types())),
                Rule::exists('categories', 'type'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipe.required' => 'Pilih Barang Bekas atau Daur Ulang terlebih dahulu.',
            'tipe.string' => 'Jenis barang tidak valid.',
            'tipe.in' => 'Jenis barang tidak valid.',
            'tipe.exists' => 'Kategori untuk jenis barang ini belum tersedia.',
        ];
    }
}
