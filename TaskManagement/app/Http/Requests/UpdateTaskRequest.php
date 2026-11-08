<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:150',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            'priority' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['low', 'medium', 'high']),
            ],

            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],

            'due_at' => [
                'sometimes',
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }
}
