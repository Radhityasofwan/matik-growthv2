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
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required','string','email','max:255', Rule::unique('leads')->ignore($leadId)],
            'phone'         => ['nullable', 'string', 'max:30'],
            'store_name'    => ['nullable', 'string', 'max:255'],
            'status'        => ['required', 'string', Rule::in(['trial','active','nonactive','converted','churn'])],
            'owner_id'      => ['required', 'integer', 'exists:users,id'],
            'trial_ends_at' => ['nullable', 'date'],
        ];
    }
}
