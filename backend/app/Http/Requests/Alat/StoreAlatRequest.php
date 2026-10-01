<?php

namespace App\Http\Requests\Alat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kategori_id' => [
                'required',
                'integer',
                Rule::exists('kategori', 'id')
            ],
            'nama_alat' => ['required', 'string', 'max:255'],
            // Prefix serial (misal "Router Mikrotik" -> "RM"). Auto-saran
            // dari JS di view create; admin bebas menimpa. Unit serial
            // sendiri dikelola lewat menu Kelola Unit, bukan form ini.
            'kode_alat' => ['required', 'string', 'max:10'],
            'deskripsi' => ['nullable', 'string'],
            'gambar' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'kategori_id.exists' => 'Kategori yang dipilih tidak valid atau tidak terdaftar.',
            'kode_alat.required' => 'Kode alat wajib diisi (prefix serial number).',
            'kode_alat.max' => 'Kode alat maksimal 10 karakter.',
            'gambar.max' => 'Ukuran gambar maksimal adalah 2 MB.',
            'gambar.image' => 'File yang diunggah harus berupa gambar.',
        ];
    }
}
