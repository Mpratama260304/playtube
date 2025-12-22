<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && !auth()->user()->is_banned;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Please write a comment.',
            'body.max' => 'Comment cannot exceed 2000 characters.',
        ];
    }
}
