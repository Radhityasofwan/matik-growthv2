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
        // Set to true as all authenticated users can manage templates.
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
                 // Ensure name is unique, but ignore the current template's name when updating
                Rule::unique('wa_templates')->ignore($this->template),
            ],
            'body' => 'required|string',
            'category' => 'nullable|string|max:100',
        ];
    }
}
