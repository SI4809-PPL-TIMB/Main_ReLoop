<?php

namespace App\Http\Requests;

use App\Models\MitraPengepul;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * 9.1.2 · Request Validasi Order Setor (PPLTIMB-170).
 */
class OrderSetorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category_id'     => ['required', 'integer', 'exists:categories,id'],
            'perkiraan_berat' => ['required', 'numeric', 'min:0.1', 'max:5000'],
            'metode'          => ['required', 'string', 'in:jemput,antar'],
            'patokan'         => ['nullable', 'string', 'max:255'],
            'catatan'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required'     => 'Silakan pilih jenis sampah yang ingin disetor.',
            'category_id.exists'       => 'Jenis sampah yang dipilih tidak valid.',
            'perkiraan_berat.required' => 'Perkiraan berat sampah wajib diisi.',
            'perkiraan_berat.numeric'  => 'Perkiraan berat harus berupa angka.',
            'perkiraan_berat.min'      => 'Perkiraan berat minimal 0,1 kg.',
            'perkiraan_berat.max'      => 'Perkiraan berat maksimal 5.000 kg.',
            'metode.required'          => 'Pilih metode penyerahan (Jemput atau Antar Sendiri).',
            'metode.in'                => 'Metode penyerahan tidak valid.',
            'patokan.max'              => 'Patokan alamat maksimal 255 karakter.',
            'catatan.max'              => 'Catatan maksimal 500 karakter.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            /** @var MitraPengepul|null $mitra */
            $mitra = $this->route('mitra');

            if (! $mitra || $mitra->status !== MitraPengepul::TERVERIFIKASI) {
                $validator->errors()->add('mitra', 'Mitra pengepul tidak valid atau belum terverifikasi.');
                return;
            }

            // 1. Kategori harus diterima oleh mitra (ada di mitra_harga)
            if ($this->category_id && ! $mitra->harga()->where('category_id', $this->category_id)->exists()) {
                $validator->errors()->add('category_id', 'Mitra ini tidak menerima jenis sampah yang dipilih.');
            }

            // 2. Validasi metode penyerahan
            if ($this->metode === 'jemput') {
                if (! $mitra->layanan_jemput) {
                    $validator->errors()->add('metode', 'Mitra ini tidak melayani penjemputan.');
                    return;
                }

                $customerKelurahan = $this->user()?->profile?->kelurahan;
                $customerKecamatanId = $customerKelurahan?->kecamatan_id;

                if (! $customerKecamatanId || ! $mitra->area()->where('kecamatan_id', $customerKecamatanId)->exists()) {
                    $validator->errors()->add('metode', 'Alamat Anda berada di luar area layanan jemput mitra ini.');
                }
            } elseif ($this->metode === 'antar') {
                if (! $mitra->layanan_antar) {
                    $validator->errors()->add('metode', 'Mitra ini tidak melayani antar sendiri ke lapak.');
                }
            }
        });
    }
}
