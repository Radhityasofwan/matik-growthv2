<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class WahaSenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Catatan: tidak minta session/number di create/update.
        // Hanya nama, deskripsi, is_default (opsional).
        return [
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default'  => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
