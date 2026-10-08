<?php

namespace App\Http\Requests\Pengembalian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePengembalianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Kunci nilai yang sama seperti StorePengembalianRequest.
            // Sebelumnya 'string|max:255' saja, jadi update bisa dipakai
            // menyelipkan kondisi di luar 'Baik'/'Rusak'.
            'kondisi_kembali' => ['sometimes', 'required', 'string', Rule::in(['Baik', 'Rusak'])],

            // Denda keterlambatan dihitung saat approve. Kolom ini boleh
            // diedit admin, tapi tetap tidak boleh negatif.
            'denda' => ['nullable', 'integer', 'min:0'],
            'denda_kerusakan' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'kondisi_kembali.in' => 'Kondisi kembali harus Baik atau Rusak.',
        ];
    }

    public function attributes(): array
    {
        return [
            'kondisi_kembali' => 'Kondisi barang kembali',
            'denda' => 'Nilai denda',
            'denda_kerusakan' => 'Denda kerusakan',
        ];
    }
}
