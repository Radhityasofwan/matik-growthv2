<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Set to true as all authenticated users can manage leads based on the roadmap.
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
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // Ensure email is unique, but ignore the current lead's email when updating
                Rule::unique('leads')->ignore($this->lead),
            ],
            'status' => ['required', Rule::in(['trial', 'active', 'converted', 'churn'])],
            'user_id' => 'required|exists:users,id',
        ];
    }
}
