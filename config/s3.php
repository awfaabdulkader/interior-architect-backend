<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AWS S3 Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for AWS S3 integration.
    | Make sure to set the following environment variables:
    | - AWS_ACCESS_KEY_ID
    | - AWS_SECRET_ACCESS_KEY
    | - AWS_DEFAULT_REGION
    | - AWS_BUCKET
    | - AWS_URL (optional, for custom endpoints)
    | - AWS_ENDPOINT (optional, for custom endpoints)
    | - AWS_USE_PATH_STYLE_ENDPOINT (optional, default: false)
    |
    */

    'default_disk' => env('FILESYSTEM_DISK', 's3'),

    'bucket' => env('AWS_BUCKET'),

    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),

    'url' => env('AWS_URL'),

    'endpoint' => env('AWS_ENDPOINT'),

    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configure default folders and file settings for different types of uploads
    |
    */

    'folders' => [
        'projects' => 'projects',
        'profiles' => 'profiles',
        'documents' => 'documents',
        'temp' => 'temp',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Validation
    |--------------------------------------------------------------------------
    |
    | Configure allowed file types and size limits
    |
    */

    'allowed_types' => [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'document' => ['pdf', 'doc', 'docx', 'txt'],
        'video' => ['mp4', 'avi', 'mov', 'wmv'],
    ],

    'max_file_size' => [
        'image' => 5 * 1024 * 1024, // 5MB
        'document' => 10 * 1024 * 1024, // 10MB
        'video' => 100 * 1024 * 1024, // 100MB
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | If you're using a CDN in front of S3, configure it here
    |
    */

    'cdn' => [
        'enabled' => env('AWS_CDN_ENABLED', false),
        'url' => env('AWS_CDN_URL'),
    ],

];
