<?php

namespace App\Services;

use App\Models\MasjidLocation;

class LocationMatchingService
{
    /**
     * Cek jarak ke SEMUA titik lokasi aktif milik masjid — ustadz boleh absen
     * di titik manapun (mis. ada beberapa kelas/cabang), diterima kalau masuk
     * radius salah satu titik.
     *
     * @return array{matched:?MasjidLocation, nearest:?MasjidLocation, nearestDistance:?float}
     */
    public function match(string $masjidId, float $lat, float $lng): array
    {
        $locations = MasjidLocation::where('masjid_id', $masjidId)->where('is_active', true)->get();

        $matched = null;
        $nearest = null;
        $nearestDistance = null;

        foreach ($locations as $location) {
            $distance = $this->haversineDistance($lat, $lng, (float) $location->lat, (float) $location->lng);

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearest = $location;
                $nearestDistance = $distance;
            }

            if ($distance <= $location->radius_meters) {
                $matched = $location;
                break;
            }
        }

        return ['matched' => $matched, 'nearest' => $nearest, 'nearestDistance' => $nearestDistance];
    }

    /**
     * Titik lokasi aktif terdekat yang masuk radiusnya (dipakai untuk
     * presensi pulang, informational — tidak pernah menolak presensi).
     */
    public function nearestWithinRadius(string $masjidId, float $lat, float $lng): ?MasjidLocation
    {
        return MasjidLocation::where('masjid_id', $masjidId)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($location) => $this->haversineDistance($lat, $lng, (float) $location->lat, (float) $location->lng))
            ->first(fn ($location) => $this->haversineDistance($lat, $lng, (float) $location->lat, (float) $location->lng) <= $location->radius_meters);
    }

    public function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
