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
    | - 'go': Use Go Video Server (best performance, recommended)
    |
    */

    'video_delivery_driver' => env('VIDEO_DELIVERY_DRIVER', 'go'),

    /*
    |--------------------------------------------------------------------------
    | Go Video Server Configuration
    |--------------------------------------------------------------------------
    |
    | High-performance Go-based video streaming server settings.
    | This server handles all video streaming with native HTTP Range support,
    | HLS/DASH adaptive streaming, and intelligent caching.
    |
    */

    'use_go_video_server' => env('USE_GO_VIDEO_SERVER', true),
    
    'go_video_server_url' => env('GO_VIDEO_SERVER_URL', 'http://localhost:8090'),
    
    'go_video_secret_key' => env('GO_VIDEO_SECRET_KEY', 'playtube-video-secret-key-change-in-production'),
    
    'signed_url_expiry' => env('SIGNED_URL_EXPIRY', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Adaptive Streaming Settings
    |--------------------------------------------------------------------------
    */

    'adaptive_streaming' => [
        // Enable HLS streaming
        'hls_enabled' => env('HLS_ENABLED', true),
        
        // Enable DASH streaming
        'dash_enabled' => env('DASH_ENABLED', false),
        
        // HLS segment duration in seconds
        'hls_segment_duration' => env('HLS_SEGMENT_DURATION', 6),
        
        // Available quality renditions
        'qualities' => [
            '360p' => [
                'width' => 640,
                'height' => 360,
                'bitrate' => '800k',
                'audio_bitrate' => '96k',
            ],
            '480p' => [
                'width' => 854,
                'height' => 480,
                'bitrate' => '1400k',
                'audio_bitrate' => '128k',
            ],
            '720p' => [
                'width' => 1280,
                'height' => 720,
                'bitrate' => '2500k',
                'audio_bitrate' => '128k',
            ],
            '1080p' => [
                'width' => 1920,
                'height' => 1080,
                'bitrate' => '5000k',
                'audio_bitrate' => '192k',
            ],
        ],
    ],

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
