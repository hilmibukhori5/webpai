<?php

namespace App\Support;

use InvalidArgumentException;

class GradeScale
{
    /**
     * Konversi nilai huruf (NH) ke bobot, sesuai config/grading.php (docs/spec.md bagian 3).
     */
    public static function toWeight(string $nh): float
    {
        $weights = config('grading.weights');
        $nh = strtoupper(trim($nh));

        if (! array_key_exists($nh, $weights)) {
            throw new InvalidArgumentException("Nilai huruf tidak dikenal: {$nh}");
        }

        return $weights[$nh];
    }
}
