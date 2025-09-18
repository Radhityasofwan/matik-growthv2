<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. You may change this to any subdomain you like.
    |
    */

    'domain' => env('HORIZON_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the path of the internal Horizon API that isn’t exposed.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | Horizon uses Redis to store information about your queues, jobs, and
    | other metrics. Here you may specify which Redis connection it will
    | use from your database.php / 'redis' connections list.
    |
    */

    'use' => env('HORIZON_USE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis so it
    | doesn't collide with other applications. You can override via env.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure how long you are willing to wait
    | (in seconds) before a queue is considered “long waiting”.
    |
    */

    'waits' => [
        'redis:default' => (int) env('HORIZON_WAIT_DEFAULT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure how long (in minutes) Horizon should keep the
    | recent / failed jobs, etc. Decrease for smaller Redis footprints.
    |
    */

    'trim' => [
        'recent'        => 60,
        'pending'       => 60,
        'completed'     => 60,
        'recent_failed' => 10080,
        'failed'        => 10080,
        'monitored'     => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's master supervisor will finish
    | the current jobs then terminate quickly on deploys / restarts.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    */

    'memory_limit' => (int) env('HORIZON_MEMORY_LIMIT', 128),

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    |
    | Configure your queue process managers per environment. You may use the
    | “auto” balancer for production and a simpler config for local usage.
    |
    */

    'environments' => [

        'production' => [
            'supervisor-1' => [
                'connection'       => 'redis',
                'queue'            => ['default'],
                'balance'          => 'auto',
                'minProcesses'     => 1,
                'maxProcesses'     => 10,
                'balanceMaxShift'  => 1,
                'balanceCooldown'  => 3,
                'tries'            => 3,
                'nice'             => 0,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue'      => ['default'],
                'balance'    => 'simple',
                'processes'  => 3,
                'tries'      => 3,
            ],
        ],
    ],
];
