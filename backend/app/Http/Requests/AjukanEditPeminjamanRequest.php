<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjukanEditPeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya pemilik peminjaman yang boleh mengajukan edit.
        $peminjaman = $this->route('peminjaman');

        return $peminjaman && $peminjaman->user_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            'tgl_kembali_plan_baru' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'alasan' => [
                'nullable',
                'string',
                'max:500',
            ],

            // Pengajuan edit adalah menambah/menghapus UNIT SERIAL spesifik.
            // Satu aksi = satu unit; tidak ada lagi angka jumlah.
            'alat_unit_id' => [
                'nullable',
                'array',
            ],
            'alat_unit_id.*' => [
                'required',
                'integer',
                'distinct',
                'exists:alat_unit,id',
            ],
            'aksi.*' => [
                'required',
                Rule::in(['tambah', 'hapus']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tgl_kembali_plan_baru.required' => 'Tanggal rencana kembali baru wajib diisi.',
            'tgl_kembali_plan_baru.after_or_equal' => 'Tanggal rencana kembali baru harus setelah atau sama dengan hari ini.',
            'alat_unit_id.*.distinct' => 'Unit yang sama tidak boleh dipilih dua kali.',
            'alat_unit_id.*.exists' => 'Unit alat yang dipilih tidak ditemukan.',
            'aksi.*.in' => 'Aksi harus berupa "tambah" atau "hapus".',
        ];
    }
}
