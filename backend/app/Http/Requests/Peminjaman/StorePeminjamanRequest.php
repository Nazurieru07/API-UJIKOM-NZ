<?php

namespace App\Http\Requests\Peminjaman;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tgl_kembali_plan' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],

            'items' => ['required', 'array', 'min:1'],

            // 1 baris = 1 unit serial spesifik. distinct wajib: satu unit
            // tidak boleh dipinjam dua kali dalam satu pengajuan.
            'items.*.alat_unit_id' => [
                'required',
                'integer',
                Rule::exists('alat_unit', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tgl_kembali_plan.required' => 'Tanggal rencana pengembalian wajib diisi.',
            'tgl_kembali_plan.date_format' => 'Format tanggal harus Tahun-Bulan-Tanggal (YYYY-MM-DD).',
            'tgl_kembali_plan.after_or_equal' => 'Tanggal rencana kembali tidak boleh di masa lalu.',
            'items.required' => 'Anda harus memilih minimal satu unit alat untuk dipinjam.',
            'items.array' => 'Format data item yang dikirim harus berupa daftar/list.',
            'items.min' => 'Anda harus memilih minimal satu unit alat untuk dipinjam.',
            'items.*.alat_unit_id.exists' => 'Unit alat yang dipilih tidak ditemukan.',
            'items.*.alat_unit_id.required' => 'Unit alat wajib dipilih.',
        ];
    }

    public function attributes(): array
    {
        return [
            'items.*.alat_unit_id' => 'Unit alat',
        ];
    }
}
