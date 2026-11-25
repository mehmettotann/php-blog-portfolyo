<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => \App\Env::get('APP_NAME', 'Adınız Soyadınız'),
        'env' => \App\Env::get('APP_ENV', 'production'),
        'debug' => \App\Env::bool('APP_DEBUG', false),
        'url' => rtrim((string) \App\Env::get('APP_URL', 'https://example.com'), '/'),
        'timezone' => \App\Env::get('APP_TIMEZONE', 'Europe/Istanbul'),
    ],
    'db' => [
        'host' => \App\Env::get('DB_HOST', '127.0.0.1'),
        'port' => \App\Env::int('DB_PORT', 3306),
        'database' => \App\Env::get('DB_DATABASE', 'blog_portfolio'),
        'username' => \App\Env::get('DB_USERNAME', 'root'),
        'password' => \App\Env::get('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    'uploads' => [
        'base_dir' => dirname(__DIR__) . '/uploads',
        'base_url' => '/uploads',
        'max_image_mb' => \App\Env::int('MAX_IMAGE_UPLOAD_MB', 10),
        'max_video_mb' => \App\Env::int('MAX_VIDEO_UPLOAD_MB', 150),
        'allowed_image_mimes' => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/avif' => 'avif',
        ],
        'allowed_video_mimes' => [
            'video/mp4'  => 'mp4',
            'video/webm' => 'webm',
        ],
    ],
    'litespeed' => [
        'enabled' => \App\Env::bool('LITESPEED_CACHE_ENABLED', false),
        'ttl' => \App\Env::int('LITESPEED_CACHE_TTL', 3600),
    ],
];
