<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The bundled dataset is the GeoNames postal code export for Germany
 * (https://download.geonames.org/export/zip/, CC BY 4.0), reduced to postcode, place, latitude, longitude.
 */
#[Signature('app:import-location-coordinates {--file= : Tab-separated file with postcode, place name, latitude, longitude}')]
#[Description('Fill latitude and longitude of all locations from a postcode coordinates file')]
class ImportLocationCoordinates extends Command
{
    /**
     * @var array<string, list<array{place: string, latitude: float, longitude: float}>>
     */
    private array $entriesByPostcode = [];

    /**
     * @var array<string, list<array{place: string, latitude: float, longitude: float}>>
     */
    private array $entriesByPlace = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = $this->option('file') ?: database_path('data/geonames-de-postcodes.tsv');

        if (! is_readable($file)) {
            $this->error("Coordinates file not found: {$file}");

            return self::FAILURE;
        }

        $this->loadEntries($file);

        $matchedBy = ['postcode and place' => 0, 'postcode' => 0, 'place name' => 0, 'neighbouring postcodes' => 0];
        $unmatched = [];

        DB::transaction(function () use (&$matchedBy, &$unmatched): void {
            Location::query()->chunkById(1000, function (Collection $locations) use (&$matchedBy, &$unmatched): void {
                foreach ($locations as $location) {
                    [$coordinates, $source] = $this->findCoordinates($location);

                    if ($coordinates === null) {
                        $unmatched[] = "{$location->postcode} {$location->city_name}";

                        continue;
                    }

                    $location->update($coordinates);
                    $matchedBy[$source]++;
                }
            });
        });

        foreach ($matchedBy as $source => $count) {
            $this->line("Matched by {$source}: {$count}");
        }

        $this->line('Without coordinates: '.count($unmatched));

        foreach ($unmatched as $location) {
            $this->line("  {$location}");
        }

        return self::SUCCESS;
    }

    private function loadEntries(string $file): void
    {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $columns = explode("\t", $line);

            if (count($columns) < 4) {
                continue;
            }

            $entry = [
                'place' => $this->normalise($columns[1]),
                'latitude' => (float) $columns[2],
                'longitude' => (float) $columns[3],
            ];

            $this->entriesByPostcode[$columns[0]][] = $entry;
            $this->entriesByPlace[$entry['place']][] = $entry;
        }
    }

    /**
     * Prefers the entry for this exact place, then the middle of the postcode area,
     * then the middle of all areas of a place with the same name, then the middle of neighbouring postcodes.
     *
     * @return array{0: array{latitude: float, longitude: float}|null, 1: string|null}
     */
    private function findCoordinates(Location $location): array
    {
        $place = $this->normalise($location->city_name);
        $postcodeEntries = $this->entriesByPostcode[$location->postcode] ?? [];

        foreach ($postcodeEntries as $entry) {
            if ($entry['place'] === $place) {
                return [['latitude' => $entry['latitude'], 'longitude' => $entry['longitude']], 'postcode and place'];
            }
        }

        if ($postcodeEntries !== []) {
            return [$this->middleOf($postcodeEntries), 'postcode'];
        }

        if (isset($this->entriesByPlace[$place])) {
            return [$this->middleOf($this->entriesByPlace[$place]), 'place name'];
        }

        foreach ([4, 3] as $prefixLength) {
            $neighbours = $this->entriesWithPostcodePrefix(substr($location->postcode, 0, $prefixLength));

            if ($neighbours !== []) {
                return [$this->middleOf($neighbours), 'neighbouring postcodes'];
            }
        }

        return [null, null];
    }

    /**
     * German postcodes are assigned by area, so postcodes sharing their leading digits lie close together.
     *
     * @return list<array{place: string, latitude: float, longitude: float}>
     */
    private function entriesWithPostcodePrefix(string $prefix): array
    {
        return collect($this->entriesByPostcode)
            ->filter(fn (array $entries, string|int $postcode): bool => str_starts_with((string) $postcode, $prefix))
            ->flatten(1)
            ->all();
    }

    /**
     * The median keeps a single far-off entry from dragging the point away.
     *
     * @param  list<array{place: string, latitude: float, longitude: float}>  $entries
     * @return array{latitude: float, longitude: float}
     */
    private function middleOf(array $entries): array
    {
        return [
            'latitude' => (float) collect($entries)->median('latitude'),
            'longitude' => (float) collect($entries)->median('longitude'),
        ];
    }

    private function normalise(string $placeName): string
    {
        return mb_strtolower(trim($placeName));
    }
}
