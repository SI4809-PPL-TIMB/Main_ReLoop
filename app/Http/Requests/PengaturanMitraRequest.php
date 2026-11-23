<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\MitraPengepul;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** 8.1.2 · Pengepul mengatur area, harga per kg, jam operasional, dan jenis layanan. */
class PengaturanMitraRequest extends FormRequest
{
    public const HARGA_MIN = 100;
    public const HARGA_MAKS = 1000000;

    public function authorize(): bool
    {
        return true; // dibatasi middleware peran:pengepul di route
    }

    /**
     * Form mengirim semua jenis sampah dan 7 hari. Yang dikosongkan / tidak dicentang dibuang dulu,
     * sehingga yang divalidasi hanya harga yang diisi dan hari yang buka.
     * Harga boleh ditulis "3.500".
     */
    protected function prepareForValidation(): void
    {
        $harga = collect((array) $this->input('harga', []))
            ->map(fn ($v) => preg_replace('/[.\s]/', '', (string) $v))
            ->reject(fn ($v) => $v === '')
            ->all();

        $jam = collect((array) $this->input('jam', []))
            ->only(array_keys(MitraPengepul::HARI))
            ->filter(fn ($j) => is_array($j) && ! empty($j['aktif']))
            ->map(fn ($j) => ['buka' => $j['buka'] ?? null, 'tutup' => $j['tutup'] ?? null])
            ->all();

        $this->merge([
            'harga'          => $harga,
            'jam'            => $jam,
            'layanan_jemput' => $this->boolean('layanan_jemput'),
            'layanan_antar'  => $this->boolean('layanan_antar'),
        ]);
    }

    public function rules(): array
    {
        return [
            'area'           => ['required', 'array', 'min:1'],
            'area.*'         => ['integer', 'distinct', Rule::exists('wilayah_kecamatan', 'id')],
            'harga'          => ['required', 'array', 'min:1'],
            'harga.*'        => ['integer', 'min:'.self::HARGA_MIN, 'max:'.self::HARGA_MAKS],
            'jam'            => ['required', 'array', 'min:1'],
            'jam.*.buka'     => ['required', 'date_format:H:i'],
            'jam.*.tutup'    => ['required', 'date_format:H:i', 'after:jam.*.buka'],
            'layanan_jemput' => ['boolean'],
            'layanan_antar'  => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $sah = Category::daurUlang()->pluck('id')->map(fn ($id) => (string) $id)->all();
                $kunci = array_map('strval', array_keys($this->input('harga', [])));
                if (array_diff($kunci, $sah)) {
                    $validator->errors()->add('harga', 'Harga hanya untuk jenis sampah daur ulang.');
                }

                if (! $this->boolean('layanan_jemput') && ! $this->boolean('layanan_antar')) {
                    $validator->errors()->add('layanan', 'Pilih minimal satu layanan: Jemput atau Antar Sendiri.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'area.required'         => 'Pilih minimal satu kecamatan yang kamu layani.',
            'area.min'              => 'Pilih minimal satu kecamatan yang kamu layani.',
            'area.*.exists'         => 'Kecamatan tidak ditemukan.',
            'harga.required'        => 'Isi harga per kg minimal untuk satu jenis sampah.',
            'harga.min'             => 'Isi harga per kg minimal untuk satu jenis sampah.',
            'harga.*.integer'       => 'Isi harga dengan angka saja.',
            'harga.*.min'           => 'Harga minimal Rp '.number_format(self::HARGA_MIN, 0, ',', '.').' per kg.',
            'harga.*.max'           => 'Harga maksimal Rp '.number_format(self::HARGA_MAKS, 0, ',', '.').' per kg.',
            'jam.required'          => 'Pilih minimal satu hari buka.',
            'jam.min'               => 'Pilih minimal satu hari buka.',
            'jam.*.buka.required'   => 'Isi jam buka.',
            'jam.*.buka.date_format' => 'Format jam buka tidak valid.',
            'jam.*.tutup.required'  => 'Isi jam tutup.',
            'jam.*.tutup.date_format' => 'Format jam tutup tidak valid.',
            'jam.*.tutup.after'     => 'Jam tutup harus setelah jam buka.',
        ];
    }
}
