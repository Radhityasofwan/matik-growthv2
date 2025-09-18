<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Izinkan semua pengguna yang terotentikasi untuk membuat/mengedit lead.
        // Anda bisa menambahkan logika otorisasi yang lebih spesifik di sini jika perlu.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Mendapatkan ID lead dari route saat berada di mode update.
        // Akan bernilai null saat berada di mode create.
        $leadId = $this->route('lead') ? $this->route('lead')->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // Pastikan email unik, kecuali untuk data lead yang sedang diedit.
                Rule::unique('leads')->ignore($leadId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string', Rule::in(['trial', 'active', 'converted', 'churn'])],
            'owner_id' => ['required', 'integer', 'exists:users,id'], // Pastikan owner_id ada di tabel users.
        ];
    }
}
