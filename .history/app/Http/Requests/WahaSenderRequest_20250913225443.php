<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WahaSenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required','string','max:100'],
            'description' => ['nullable','string','max:255'],
            'is_default'  => ['sometimes','boolean'],
            // number & session tidak diminta saat create
        ];
    }
}
