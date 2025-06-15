<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Content Delivery Network integration
    | Supports static assets and translation exports caching
    |
    */

    'enabled' => env('CDN_ENABLED', false),

    'base_url' => env('CDN_BASE_URL', ''),

    'disk' => env('CDN_DISK', 'cdn'),

    'providers' => [
        'cloudfront' => [
            'distribution_id' => env('CLOUDFRONT_DISTRIBUTION_ID'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'invalidation_endpoint' => 'https://cloudfront.amazonaws.com/2020-05-31/distribution/{distribution_id}/invalidation',
        ],

        'cloudflare' => [
            'zone_id' => env('CLOUDFLARE_ZONE_ID'),
            'api_token' => env('CLOUDFLARE_API_TOKEN'),
            'invalidation_endpoint' => 'https://api.cloudflare.com/client/v4/zones/{zone_id}/purge_cache',
        ],
    ],

    'cache' => [
        'ttl' => env('CDN_CACHE_TTL', 3600), // 1 hour
        'max_age' => env('CDN_MAX_AGE', 86400), // 24 hours
    ],

    'assets' => [
        'css' => [
            'cache_control' => 'public, max-age=31536000', // 1 year
            'content_type' => 'text/css',
        ],
        'js' => [
            'cache_control' => 'public, max-age=31536000', // 1 year
            'content_type' => 'application/javascript',
        ],
        'json' => [
            'cache_control' => 'public, max-age=3600', // 1 hour for translation exports
            'content_type' => 'application/json',
        ],
    ],

    'translations' => [
        'auto_upload' => env('CDN_AUTO_UPLOAD_TRANSLATIONS', false),
        'cache_invalidation' => env('CDN_CACHE_INVALIDATION', true),
        'compression' => env('CDN_COMPRESSION', true),
    ],
];
