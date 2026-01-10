<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for video and image uploads.
    |
    */

    'upload' => [
        // Maximum video file size in KB (default: 512MB = 524288 KB)
        'max_size_kb' => env('UPLOAD_MAX_SIZE_KB', 524288),
        
        // Allowed video MIME types
        'allowed_video_types' => [
            'video/mp4',
            'video/quicktime',
            'video/x-msvideo',
            'video/webm',
            'video/x-matroska',
        ],
        
        // Maximum thumbnail size in KB (default: 5MB)
        'max_thumbnail_kb' => env('UPLOAD_MAX_THUMBNAIL_KB', 5120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Processing
    |--------------------------------------------------------------------------
    */

    'processing' => [
        // Whether to generate thumbnails (requires ffmpeg)
        'generate_thumbnail' => env('VIDEO_GENERATE_THUMBNAIL', true),
        
        // Default thumbnail if auto-generation fails
        'default_thumbnail' => '/images/default-thumbnail.jpg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Delivery
    |--------------------------------------------------------------------------
    |
    | Driver for video delivery:
    | - 'php': Stream through PHP (development, lower performance)
    | - 'nginx': Use X-Accel-Redirect (production, optimal performance)
    |
    */

    'video_delivery_driver' => env('VIDEO_DELIVERY_DRIVER', 'php'),

    /*
    |--------------------------------------------------------------------------
    | Shorts Settings
    |--------------------------------------------------------------------------
    */

    'shorts' => [
        // Maximum duration in seconds for shorts
        'max_duration' => env('SHORTS_MAX_DURATION', 60),
    ],
];
