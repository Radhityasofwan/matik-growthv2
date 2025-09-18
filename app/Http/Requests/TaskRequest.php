<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'link'        => 'nullable|string|max:1000',
            'due_date'    => 'nullable|date',
            'priority'    => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assignee_id' => 'nullable|exists:users,id',
            'owner_ids'   => 'nullable|array',
            'owner_ids.*' => 'integer|exists:users,id',
        ];

        // Saat update, izinkan perubahan status ke 4 kolom kanban (termasuk review/preview)
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['status'] = ['required', Rule::in(['open', 'in_progress', 'review', 'done'])];
        }

        return $rules;
    }
}
