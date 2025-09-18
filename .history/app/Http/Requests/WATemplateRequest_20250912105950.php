<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WATemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Ambil ID dari route model binding 'template' (Model atau id)
        $tpl   = $this->route('template');
        $tplId = is_object($tpl) ? $tpl->getKey() : (is_numeric($tpl) ? (int)$tpl : null);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wa_templates', 'name')->ignore($tplId),
            ],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama template wajib diisi.',
            'name.unique'       => 'Nama template sudah digunakan.',
            'body.required'     => 'Body template wajib diisi.',
            'is_active.boolean' => 'Status template tidak valid.',
        ];
    }
}
