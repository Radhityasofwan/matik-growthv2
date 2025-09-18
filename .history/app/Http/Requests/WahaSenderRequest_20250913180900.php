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
        $id = $this->route('wahaSender')?->id ?? $this->route('waha_sender')?->id;

        $rules = [
            'name'        => ['required','string','max:100'],
            'description' => ['nullable','string','max:255'],
            // session tidak wajib: akan diisi lewat proses scan QR
            'session'     => ['sometimes','nullable','string','max:150'],
            'number'      => ['required','string','max:30'],
            'is_active'   => ['sometimes','boolean'],
            'is_default'  => ['sometimes','boolean'],
            'display_name'=> ['sometimes','nullable','string','max:150'],
        ];

        // validasi unik jika kolomnya ada
        $uniqueRules = [];
        if (Schema::hasColumn('waha_senders', 'session_name')) {
            $uniqueRules[] = Rule::unique('waha_senders', 'session_name')->ignore($id);
        }
        if (Schema::hasColumn('waha_senders', 'session')) {
            $uniqueRules[] = Rule::unique('waha_senders', 'session')->ignore($id);
        }
        if ($uniqueRules) $rules['session'] = array_merge($rules['session'], $uniqueRules);

        return $rules;
    }

    public function messages(): array
    {
        return [
            'session.unique' => 'Session sudah dipakai oleh sender lain.',
        ];
    }
}
