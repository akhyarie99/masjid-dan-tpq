<?php

use App\Models\Masjid;

if (! function_exists('tenant')) {
    /**
     * Tenant (masjid) yang sudah diresolusi dari host request oleh
     * ResolveTenant middleware. Null di domain pusat (landing/daftar) atau
     * di context console/queue yang tidak punya request.
     */
    function tenant(): ?Masjid
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request()->attributes->get('tenant');
    }
}
