<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WATemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Sesuaikan dengan kebijakan auth kamu
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Abaikan ID template saat update; aman untuk route-model binding "template"
                Rule::unique('wa_templates', 'name')->ignore(optional($this->template)->id),
            ],
            'body' => ['required', 'string'],
            // Optional boolean; default akan di-handle di controller
            'is_active' => ['sometimes', 'boolean'],
            // Catatan: "category" dihapus agar konsisten dengan skema DB saat ini
        ];
    }
}
