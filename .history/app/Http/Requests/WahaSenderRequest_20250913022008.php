<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WahaSenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('wahaSender')?->id;

        return [
            'name'        => ['required','string','max:100'],
            'description' => ['nullable','string','max:255'],
            'session'     => ['required','string','max:150', Rule::unique('waha_senders','session')->ignore($id)],
            'number'      => ['required','string','max:30'],
            'is_active'   => ['sometimes','boolean'],
            'is_default'  => ['sometimes','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'session.unique' => 'Session sudah dipakai oleh sender lain.',
        ];
    }
}
