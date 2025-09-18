<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Set to true as all authenticated users can manage campaigns.
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
        'description' => 'nullable|string',
        'channel' => 'required|string|max:255',
        'status' => ['required', Rule::in(['planned', 'active', 'completed', 'paused'])],
        'budget' => 'required|numeric|min:0',
        'revenue' => 'nullable|numeric|min:0',
        'owner_id' => 'required|exists:users,id', // <-- Tambahkan ini
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }
}
