<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    | For Railway/production: Set FILESYSTEM_DISK=s3 and configure S3/R2 credentials
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            // Use relative URL to work with any domain (Cloudflare tunnel, localhost, etc.)
            'url' => '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | S3-Compatible Storage (AWS S3, Cloudflare R2, MinIO, etc.)
        |--------------------------------------------------------------------------
        |
        | For production deployments on Railway (ephemeral filesystem), configure:
        |
        | AWS S3:
        |   AWS_ACCESS_KEY_ID=your-key
        |   AWS_SECRET_ACCESS_KEY=your-secret
        |   AWS_DEFAULT_REGION=us-east-1
        |   AWS_BUCKET=your-bucket-name
        |
        | Cloudflare R2:
        |   AWS_ACCESS_KEY_ID=your-r2-access-key
        |   AWS_SECRET_ACCESS_KEY=your-r2-secret-key
        |   AWS_DEFAULT_REGION=auto
        |   AWS_BUCKET=your-r2-bucket
        |   AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
        |   AWS_USE_PATH_STYLE_ENDPOINT=true
        |
        */
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
