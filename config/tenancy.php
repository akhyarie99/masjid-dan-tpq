<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Central Domain
    |--------------------------------------------------------------------------
    |
    | Domain pusat SaaS (landing page + pendaftaran lembaga baru). Setiap
    | lembaga/tenant otomatis dapat subdomain "{slug}.<central_domain>",
    | dan boleh menambahkan custom domain sendiri setelah verifikasi DNS.
    |
    */
    'central_domain' => env('CENTRAL_DOMAIN', 'tpq.smartedugame.com'),

    /*
    |--------------------------------------------------------------------------
    | Legacy Root Redirect Slug
    |--------------------------------------------------------------------------
    |
    | Tenant pertama yang sebelumnya live langsung di root domain (sebelum
    | domain itu dijadikan hub SaaS). QR code fisik & link WA lama yang sudah
    | terlanjur beredar mengarah ke root domain — redirect sementara di
    | routes/central.php memakai slug ini supaya link lama tidak mati total.
    |
    */
    'legacy_root_redirect_slug' => env('LEGACY_ROOT_REDIRECT_SLUG'),
];
