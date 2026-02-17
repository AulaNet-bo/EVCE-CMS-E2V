<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Steve Data Source
    |--------------------------------------------------------------------------
    | mysql: reads directly from Steve DB
    | redis: reads from observer cache (steve-observer)
    */
    'data_source' => env('STEVE_DATA_SOURCE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Redis Key Prefix produced by steve-observer
    |--------------------------------------------------------------------------
    */
    'redis_prefix' => env('STEVE_REDIS_PREFIX', 'steve'),
];
