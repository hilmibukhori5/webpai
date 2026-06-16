<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Skala Nilai (NH -> Bobot)
    |--------------------------------------------------------------------------
    |
    | Lihat docs/spec.md bagian 3. Dipakai untuk konversi nilai huruf (NH)
    | ke bobot saat menghitung rata-rata tertimbang SKS (PKS Lama).
    |
    */
    'weights' => [
        'A' => 4.0,
        'B+' => 3.5,
        'B' => 3.0,
        'C+' => 2.5,
        'C' => 2.0,
        'D+' => 1.5,
        'D' => 1.0,
        'E' => 0.0,
    ],

];
