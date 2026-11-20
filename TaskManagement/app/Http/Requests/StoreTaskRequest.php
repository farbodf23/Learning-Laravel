<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[FailOnUnknownFields]
class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $store = $this->route('store');

        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'priority' => [
                'sometimes',
                'string',
                Rule::in(['low', 'medium', 'high']),
            ],

            'due_at' => [
                'nullable',
                'date',
                'after:now',
            ],

            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('store_memberships', 'user_id')
                    ->where('store_id', $store->id),
            ],
        ];
    }
}
