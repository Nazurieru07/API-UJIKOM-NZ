<?php

namespace App\Http\Requests\Alat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Mengambil ID alat yang sedang di-update dari parameter route URL
        $alatId = $this->route('alat');

        return [
            'kategori_id' => [
                'required',
                'integer',
                Rule::exists('kategori', 'id')
            ],
            'nama_alat' => ['required', 'string', 'max:255'],
            // Prefix serial. Ubah prefix tidak mengubah serial unit yang
            // sudah ada (serial lama tetap dipakai di detail_pinjam lama);
            // hanya mempengaruhi serial untuk unit baru.
            'kode_alat' => ['required', 'string', 'max:10'],
            'deskripsi' => ['nullable', 'string'],
            'gambar' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_alat.required' => 'Kode alat wajib diisi (prefix serial number).',
            'kode_alat.max' => 'Kode alat maksimal 10 karakter.',
        ];
    }
}
