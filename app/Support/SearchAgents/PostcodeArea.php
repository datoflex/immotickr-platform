<?php

namespace App\Support\SearchAgents;

use App\Models\Location;

final class PostcodeArea
{
    private const EARTH_RADIUS_KM = 6371.0;

    private const KM_PER_DEGREE_OF_LATITUDE = 111.0;

    /**
     * All postcodes whose location lies within the radius around the given postcode, itself included.
     *
     * Without a radius, or when the postcode has no coordinates, only the postcode itself matches.
     *
     * @return list<string>
     */
    public static function postcodesWithin(string $postcode, float $radiusKm): array
    {
        if ($radiusKm <= 0) {
            return [$postcode];
        }

        $centre = Location::query()
            ->where('postcode', $postcode)
            ->whereNotNull('latitude')
            ->selectRaw('avg(latitude) as latitude, avg(longitude) as longitude')
            ->first();

        if ($centre?->latitude === null) {
            return [$postcode];
        }

        $latitudeMargin = $radiusKm / self::KM_PER_DEGREE_OF_LATITUDE;
        $longitudeMargin = $radiusKm / (self::KM_PER_DEGREE_OF_LATITUDE * cos(deg2rad($centre->latitude)));

        // The database narrows the candidates to a square; the exact distance is checked here.
        return Location::query()
            ->whereBetween('latitude', [$centre->latitude - $latitudeMargin, $centre->latitude + $latitudeMargin])
            ->whereBetween('longitude', [$centre->longitude - $longitudeMargin, $centre->longitude + $longitudeMargin])
            ->get(['id', 'postcode', 'latitude', 'longitude'])
            ->filter(fn (Location $location): bool => self::distanceInKm($centre, $location) <= $radiusKm)
            ->pluck('postcode')
            ->push($postcode)
            ->unique()
            ->values()
            ->all();
    }

    private static function distanceInKm(Location $from, Location $to): float
    {
        $latitudeDelta = deg2rad($to->latitude - $from->latitude);
        $longitudeDelta = deg2rad($to->longitude - $from->longitude);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($from->latitude)) * cos(deg2rad($to->latitude)) * sin($longitudeDelta / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($haversine)));
    }
}
