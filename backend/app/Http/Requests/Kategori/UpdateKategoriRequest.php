<?php

namespace App\Http\Requests\Kategori;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; //pastikan bernilai true karena otorisasi sudah dihandle oleh middleware di route
    }

    public function rules(): array
    {
        $kategori = $this->route('kategori');
        return [
            'nama_kategori' => [
                'required',
                'string',
                'max:255',
                // Mengabaikan pengecekan unik untuk ID kategpri yang sedang di-update\

                Rule::unique('kategori', 'nama_kategori')->ignore($kategori),
            ],
        ];
    }
}