<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && !auth()->user()->is_banned;
    }

    /**
     * Handle a failed validation attempt - return JSON for AJAX requests
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson() || $this->ajax()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422));
        }

        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        // Get max upload size from Site Settings (MB), convert to KB
        // Fallback to config, then to 512MB default
        $maxSizeMb = (int) Setting::get('max_upload_size', config('playtube.upload.max_size_kb', 524288) / 1024);
        $maxSizeKb = $maxSizeMb * 1024; // Convert MB to KB for Laravel validation
        
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'video' => [
                'required',
                'file',
                'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska',
                "max:{$maxSizeKb}",
            ],
            'thumbnail' => ['nullable', 'image', 'max:5120'], // 5MB for thumbnails
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'is_short' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        // Get max upload size from Site Settings for error message
        $maxSizeMb = (int) Setting::get('max_upload_size', 512);
        
        return [
            'video.required' => 'Please select a video file to upload.',
            'video.mimetypes' => 'The video must be a valid video file (MP4, MOV, AVI, WebM, or MKV).',
            'video.max' => "The video file size cannot exceed {$maxSizeMb}MB.",
            'title.required' => 'Please provide a title for your video.',
            'thumbnail.image' => 'The thumbnail must be an image file.',
            'thumbnail.max' => 'The thumbnail cannot exceed 5MB.',
        ];
    }
}
