<?php

return [
    /*
     * Threshold jarak Euclidean untuk mobile_descriptor (embedding on-device dari
     * app Flutter, MobileFaceNet 192-d, L2-normalized).
     *
     * WAJIB dikalibrasi ulang dengan data nyata dari device yang benar-benar
     * dipakai ustadz — nilai default di bawah adalah titik awal yang wajar,
     * bukan nilai final. Terlalu kecil = sering gagal cocok wajah sendiri;
     * terlalu besar = gampang ketuker orang lain.
     */
    'mobile_match_threshold' => env('FACE_MOBILE_MATCH_THRESHOLD', 1.0),
];
