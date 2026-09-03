<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\VoterServiceProvider;

return [
    AppServiceProvider::class,
    VoterServiceProvider::class,
    AuthServiceProvider::class,
];
