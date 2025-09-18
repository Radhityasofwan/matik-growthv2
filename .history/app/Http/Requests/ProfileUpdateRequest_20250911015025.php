<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pengguna yang sudah login boleh mengubah profilnya
        return true;
    }

    /**
     * Rules untuk update profil (nama, email, avatar).
     * - Avatar opsional, tapi jika dikirim harus berupa gambar yang valid.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($this->user()->id),
            ],

            // Avatar: opsional, file gambar, ukuran maks 2MB, dimensi minimal 100x100
            'avatar' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,gif,svg',
                'max:2048', // KB -> 2 MB
                'dimensions:min_width=100,min_height=100',
            ],
        ];
    }

    /**
     * Normalisasi ringan sebelum validasi (opsional tapi membantu).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->email ? strtolower(trim($this->email)) : $this->email,
            'name'  => $this->name ? trim($this->name) : $this->name,
        ]);
    }

    /**
     * Pesan error kustom.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max'      => 'Nama tidak boleh lebih dari :max karakter.',

            'email.required'  => 'Email wajib diisi.',
            'email.email'     => 'Format email tidak valid.',
            'email.lowercase' => 'Email harus menggunakan huruf kecil.',
            'email.max'       => 'Email tidak boleh lebih dari :max karakter.',
            'email.unique'    => 'Email sudah digunakan pengguna lain.',

            'avatar.file'        => 'Avatar harus berupa file.',
            'avatar.image'       => 'Avatar harus berupa gambar.',
            'avatar.mimes'       => 'Avatar harus bertipe: jpg, jpeg, png, webp, gif, atau svg.',
            'avatar.max'         => 'Ukuran avatar maks :max KB (≈2 MB).',
            'avatar.dimensions'  => 'Dimensi avatar minimal 100x100 piksel.',
        ];
    }

    /**
     * Nama atribut yang lebih ramah untuk error message.
     */
    public function attributes(): array
    {
        return [
            'name'   => 'nama',
            'email'  => 'email',
            'avatar' => 'avatar',
        ];
    }
}
