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
        // dukung dua nama parameter route: {wahaSender} & {waha_sender}
        $id = $this->route('wahaSender')?->id ?? $this->route('waha_sender')?->id;

        // tentukan kolom unik yang tersedia di DB
        $sessionColumn = Schema::hasColumn('waha_senders', 'session')
            ? 'session'
            : (Schema::hasColumn('waha_senders', 'session_name') ? 'session_name' : 'session');

        return [
            'name'        => ['required','string','max:100'],
            'description' => ['nullable','string','max:255'],
            'session'     => ['required','string','max:150', Rule::unique('waha_senders', $sessionColumn)->ignore($id)],
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
