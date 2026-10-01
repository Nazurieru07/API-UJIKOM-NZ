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
            'foto_profile'  => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }
}