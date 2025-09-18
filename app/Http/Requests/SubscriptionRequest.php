<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all authenticated users to make this request for now.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'lead_id' => 'required|exists:leads,id',
            'plan' => 'required|string|in:basic,premium,enterprise',
            'status' => 'required|string|in:active,paused,cancelled',
            'amount' => 'required|numeric|min:0',
            'cycle' => 'required|string|in:monthly,yearly',
            'auto_renew' => 'required|boolean',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ];
    }
}
