<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // hak akses diperiksa di controller lewat ListingPolicy
    }

    protected function prepareForValidation(): void
    {
        // harga boleh ditulis "350.000"
        $this->merge([
            'price'         => preg_replace('/[.\s]/', '', (string) $this->input('price')),
            'is_negotiable' => $this->boolean('is_negotiable'),
        ]);
    }

    public function isDraft(): bool
    {
        return $this->input('action') === 'draft';
    }

    public function rules(): array
    {
        // shortcut: kolom judul/harga/kategori/kondisi NOT NULL di DB, jadi draft hanya boleh tanpa foto; upgrade dengan migrasi nullable bila perlu draft setengah isi.
        $draft = $this->isDraft();
        $sudahAda = $this->route('listing')?->photos()->count() ?? 0;
        $dihapus = count($this->input('remove_photos', []));

        return [
            'title'          => ['required', 'string', 'min:5', 'max:200'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'category_id'    => ['required', Rule::exists('categories', 'id')->where('type', Category::TYPE_BEKAS)],
            'condition'      => ['required', Rule::in(array_keys(Listing::KONDISI))],
            'condition_note' => ['nullable', 'string', 'max:120'],
            'price'          => ['required', 'integer', 'min:1000', 'max:999999999'],
            'is_negotiable'  => ['boolean'],
            'material'       => ['nullable', 'string', 'max:100'],
            'size'           => ['nullable', 'string', 'max:100'],
            'usage_duration' => ['nullable', 'string', 'max:100'],
            'quantity'       => ['nullable', 'integer', 'min:1', 'max:99'],
            'photos'         => [
                'array',
                'max:'.max(0, Listing::MAKS_FOTO - $sudahAda + $dihapus),
                Rule::when(! $draft && $sudahAda - $dihapus < 1, 'required', 'nullable'),
            ],
            'photos.*'       => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_photos'  => ['array'],
            'remove_photos.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'Judul wajib diisi.',
            'title.min'            => 'Judul minimal 5 karakter.',
            'category_id.required' => 'Pilih kategori barang.',
            'category_id.exists'   => 'Kategori tidak valid. Sampah daur ulang disetor lewat pengepul.',
            'condition.required'   => 'Pilih kondisi barang.',
            'price.required'       => 'Harga wajib diisi.',
            'price.integer'        => 'Isi harga dengan angka saja.',
            'price.min'            => 'Harga minimal Rp 1.000.',
            'photos.required'      => 'Tambahkan minimal 1 foto.',
            'photos.max'           => 'Maksimal '.Listing::MAKS_FOTO.' foto per barang.',
            'photos.*.image'       => 'File harus berupa gambar.',
            'photos.*.mimes'       => 'Foto harus JPG, PNG, atau WebP.',
            'photos.*.max'         => 'Ukuran satu foto maksimal 10 MB.',
        ];
    }
}
