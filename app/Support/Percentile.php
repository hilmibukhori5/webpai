<?php

namespace App\Support;

use InvalidArgumentException;

class Percentile
{
    /**
     * Implementasi Excel PERCENTILE.INC: persentil ke-k (inklusif) dari sebuah
     * himpunan nilai. $percentile dalam skala 0-100 (mis. 80 untuk persentil ke-80),
     * sesuai pai_modules.percentile (docs/spec.md bagian 4a).
     *
     * @param  array<int, float|int>  $values
     */
    public static function inc(array $values, float $percentile): float
    {
        if (empty($values)) {
            throw new InvalidArgumentException('Tidak bisa hitung percentile dari himpunan nilai kosong.');
        }

        if ($percentile < 0 || $percentile > 100) {
            throw new InvalidArgumentException('Percentile harus di antara 0 dan 100.');
        }

        $sorted = $values;
        sort($sorted, SORT_NUMERIC);
        $sorted = array_values($sorted);

        $n = count($sorted);

        if ($n === 1) {
            return (float) $sorted[0];
        }

        $k = $percentile / 100;
        $rank = $k * ($n - 1);
        $lowerIndex = (int) floor($rank);
        $upperIndex = (int) ceil($rank);
        $fraction = $rank - $lowerIndex;

        return $sorted[$lowerIndex] + $fraction * ($sorted[$upperIndex] - $sorted[$lowerIndex]);
    }
}
