<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi form "Profil Saya".
 *
 * Data dasar (nama, email, no_hp, jenis_kelamin) divalidasi dengan
 * format yang benar; email harus unik tapi boleh sama dengan email
 * user sendiri. Password opsional -- hanya divalidasi kalau diisi.
 *
 * Rate limit 3 percobaan per menit menahan percobaan berulang tanpa
 * mengganggu user yang sedang mengoreksi profilnya sendiri.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'jenis_kelamin' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'foto_profile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }

    /**
     * Data dasar yang lolos validasi. Password dan foto tidak ikut
     * di sini: keduanya opsional dan punya penanganan sendiri di
     * controller.
     */
    public function profileData(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'no_hp' => $this->no_hp,
            'jenis_kelamin' => $this->jenis_kelamin,
        ], fn ($value) => ! is_null($value));
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $key = 'profile-update:' . $this->user()->id . ':' . $this->ip();

            if (RateLimiter::tooManyAttempts($key, 3)) {
                $validator->errors()->add(
                    'name',
                    'Terlalu banyak percobaan. Coba lagi dalam '
                    . RateLimiter::availableIn($key) . ' detik.'
                );

                return;
            }

            // Hit hanya setelah semua field lolos, supaya user yang
            // sekadar salah ketik tidak ikut terkunci.
            if ($validator->errors()->count() === 0) {
                RateLimiter::hit($key, 60);
            }
        });
    }
}
