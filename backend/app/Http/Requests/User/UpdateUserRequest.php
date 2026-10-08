<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof \App\Models\User ? $user->id : $user;
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId),],
            'password'      => ['nullable', 'string', Password::min(8)->letters()->numbers()],
            'role'          => ['required', Rule::in(['admin', 'petugas', 'peminjam'])],
            'no_hp'         => ['nullable', 'string', 'max:15'],
            'alamat'        => ['nullable', 'string'],

            // Konsisten dengan StoreUserRequest dan dengan versi web.
            // nullable: boleh tidak dikirim; tapi kalau dikirim harus salah
            // satu dari dua nilai ini.
            'jenis_kelamin' => ['nullable', 'string', Rule::in(['Laki-laki', 'Perempuan'])],

            'foto_profile'  => ['nullable', 'image', 'mimes:jpeg,png,jpg'],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan.',
        ];
    }
}
