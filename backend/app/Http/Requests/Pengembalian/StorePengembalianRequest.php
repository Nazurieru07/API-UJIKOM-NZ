<?php

namespace App\Http\Requests\Pengembalian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePengembalianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'peminjaman_id' => [
                'required',
                'integer',
                Rule::exists('peminjaman', 'id')
            ],

            // Sama seperti web (PetugasController::ajukanPengembalian):
            // hanya dua nilai sah. Sebelumnya kolom ini bebas teks apa
            // pun, padahal AdminController::setujuiPengembalian memetakan
            // selain 'Baik' jadi 'rusak' -- nilai aneh bikin data unit
            // tidak akurat.
            'kondisi_kembali' => ['required', 'string', Rule::in(['Baik', 'Rusak'])],

            // Denda kerusakan diisi petugas; denda keterlambatan dihitung
            // sistem saat admin menyetujui, jadi tidak ada di sini.
            'denda_kerusakan' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'kondisi_kembali.in' => 'Kondisi kembali harus Baik atau Rusak.',
            'denda_kerusakan.min' => 'Denda kerusakan tidak boleh negatif.',
        ];
    }

    public function attributes(): array
    {
        return [
            'peminjaman_id' => 'ID Peminjaman',
            'kondisi_kembali' => 'Kondisi barang kembali',
            'denda_kerusakan' => 'Denda kerusakan',
        ];
    }
}
