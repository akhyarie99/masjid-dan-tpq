<?php

namespace App\Services;

class FaceRecognitionService
{
    /**
     * Jarak Euclidean antara dua descriptor wajah.
     *
     * @param  array<int,float>  $a
     * @param  array<int,float>  $b
     */
    public function distance(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException('Descriptor wajah harus punya dimensi yang sama.');
        }

        $sumSquares = 0.0;
        foreach ($a as $i => $value) {
            $sumSquares += ($value - $b[$i]) ** 2;
        }

        return sqrt($sumSquares);
    }

    /**
     * True jika dua descriptor dianggap wajah orang yang sama (jarak <= threshold).
     *
     * @param  array<int,float>  $captured
     * @param  array<int,float>  $stored
     */
    public function isMatch(array $captured, array $stored, float $threshold): bool
    {
        return $this->distance($captured, $stored) <= $threshold;
    }

    /**
     * Validasi bentuk descriptor: array angka float/int dengan panjang wajar
     * (embedding on-device MobileFaceNet = 192-d, tapi dibuat fleksibel
     * kalau modelnya diganti nanti).
     */
    public function isValidDescriptor(mixed $descriptor, int $minLength = 32, int $maxLength = 512): bool
    {
        if (! is_array($descriptor) || count($descriptor) < $minLength || count($descriptor) > $maxLength) {
            return false;
        }

        foreach ($descriptor as $value) {
            if (! is_numeric($value)) {
                return false;
            }
        }

        return true;
    }
}
