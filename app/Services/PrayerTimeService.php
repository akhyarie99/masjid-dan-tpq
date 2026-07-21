<?php

namespace App\Services;

use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use IslamicNetwork\PrayerTimes\Method;
use IslamicNetwork\PrayerTimes\PrayerTimes;

class PrayerTimeService
{
    /**
     * Calculate the six daily prayer times for a given coordinate and date.
     *
     * @return array{fajr:string,sunrise:string,dhuhr:string,asr:string,maghrib:string,isha:string}
     */
    public function calculate(float $lat, float $lng, Carbon $date): array
    {
        $prayerTimes = new PrayerTimes(Method::METHOD_KEMENAG);

        $date = new DateTime($date->format('Y-m-d'), new DateTimeZone('Asia/Jakarta'));

        $times = $prayerTimes->getTimes(
            $date,
            $lat,
            $lng,
            null,
            PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE,
            null,
            PrayerTimes::TIME_FORMAT_24H
        );

        return [
            'fajr' => $times[PrayerTimes::FAJR],
            'sunrise' => $times[PrayerTimes::SUNRISE],
            'dhuhr' => $times[PrayerTimes::ZHUHR],
            'asr' => $times[PrayerTimes::ASR],
            'maghrib' => $times[PrayerTimes::MAGHRIB],
            'isha' => $times[PrayerTimes::ISHA],
        ];
    }
}
