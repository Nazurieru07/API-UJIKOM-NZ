<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password'      => ['required', 'string', Password::min(8)->letters()->numbers()],
            'role'          => ['required', Rule::in(['admin', 'petugas', 'peminjam'])],
            'no_hp'         => ['nullable', 'string', 'max:15'],
            'alamat'        => ['nullable', 'string'],

            // Sama seperti web (AdminController::storeUser) dan profil
            // mandiri (UpdateProfileRequest): hanya dua nilai sah, selain
            // itu ditolak.
            'jenis_kelamin' => ['nullable', 'string', Rule::in(['Laki-laki', 'Perempuan'])],

            'foto_profile'  => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan.',
        ];
    }
}
