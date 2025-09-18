<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $leadId = $this->route('lead') ? $this->route('lead')->id : null;

        return [
            'name'           => ['required','string','max:255'],
            'email'          => ['required','string','email','max:255', Rule::unique('leads')->ignore($leadId)],
            'phone'          => ['nullable','string','max:30'],
            'store_name'     => ['nullable','string','max:255'],
            // Manual options (Indonesia): Aktif/ Tidak Aktif/ Konversi/ Dibatalkan
            // DB values: active|nonactive|converted|churn (+ trial internal)
            'status'         => ['required','string', Rule::in(['active','nonactive','converted','churn','trial'])],
            'owner_id'       => ['required','integer','exists:users,id'],
            // Tanggal Daftar (opsional di form). Jika diisi, dipakai untuk set created_at.
            'registered_at'  => ['nullable','date'],
            // Tanggal Habis harus >= registered_at bila registered_at diisi
            'trial_ends_at'  => ['nullable','date','after_or_equal:registered_at'],
        ];
    }
}
