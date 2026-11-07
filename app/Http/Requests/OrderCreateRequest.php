<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'delivery_method' => [
                'required',
                'string',
                Rule::in([Order::DELIVERY_COD, Order::DELIVERY_AMBIL]),
            ],
            'delivery_address' => [
                'nullable',
                'string',
                'max:500',
            ],
            'offer_id' => [
                'nullable',
                'integer',
                'exists:offers,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_method.required' => 'Pilih cara serah terima barang terlebih dahulu.',
            'delivery_method.in'       => 'Metode serah terima tidak valid.',
            'delivery_address.max'     => 'Alamat pengiriman maksimal 500 karakter.',
        ];
    }
}
