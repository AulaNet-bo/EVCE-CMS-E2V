<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Steve Data Source
    |--------------------------------------------------------------------------
    | mysql: reads directly from Steve DB
    | redis: reads from observer cache (steve-observer)
    */
    'data_source' => env('STEVE_DATA_SOURCE', 'mysql'),

    // If true, all Steve reads are forced to Redis regardless of env value.
    'force_redis_reads' => filter_var(env('STEVE_FORCE_REDIS_READS', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Redis Key Prefix produced by steve-observer
    |--------------------------------------------------------------------------
    */
    'redis_prefix' => env('STEVE_REDIS_PREFIX', 'steve'),
];
