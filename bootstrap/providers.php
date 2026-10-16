<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\VoterServiceProvider;

return [
    AppServiceProvider::class,
    VoterServiceProvider::class,
    AuthServiceProvider::class,

    // Didaftarkan eksplisit agar binding `dompdf.wrapper` tetap ada meski
    // cache package-discovery basi (gejala: "Target class [dompdf.wrapper]
    // does not exist" pada export PDF). Package ini ada di composer.json.
    Barryvdh\DomPDF\ServiceProvider::class,
];
