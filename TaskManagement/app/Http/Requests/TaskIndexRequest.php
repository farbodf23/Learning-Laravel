<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[FailOnUnknownFields]
class TaskIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                Rule::in(['todo', 'in_progress', 'done', 'cancelled']),
            ],

            'priority' => [
                'sometimes',
                'string',
                Rule::in(['low', 'medium', 'high']),
            ],

            'assigned_to' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'overdue' => [
                'sometimes',
                'required',
                Rule::in(['true', 'false', '1', '0']),
            ],

            'q' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
