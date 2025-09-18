<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
            'priority'    => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assignee_id' => 'nullable|exists:users,id',
        ];

        // Status hanya wajib saat proses update
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['status'] = ['required', Rule::in(['open', 'in_progress', 'done', 'overdue'])];
        }

        return $rules;
    }
}

