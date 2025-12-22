<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $video = $this->route('video');
        return auth()->check() && (
            auth()->user()->id === $video->user_id ||
            auth()->user()->isAdmin()
        );
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'is_short' => ['boolean'],
            'thumbnail' => ['nullable', 'image', 'max:5120'], // 5MB
        ];
    }
}
