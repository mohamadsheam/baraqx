<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\RateLimitServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    HorizonServiceProvider::class,
    RateLimitServiceProvider::class,
    TelescopeServiceProvider::class,
];
