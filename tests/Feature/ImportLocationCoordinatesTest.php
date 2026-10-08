<?php

namespace Tests\Feature;

use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLocationCoordinatesTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = tempnam(sys_get_temp_dir(), 'coordinates');

        file_put_contents($this->file, implode("\n", [
            "01561\tGroßenhain\t51.2890\t13.5340",
            "01561\tEbersbach\t51.2330\t13.6500",
            "01561\tThiendorf\t51.2940\t13.7410",
            "04860\tTorgau\t51.5600\t13.0040",
        ]));
    }

    protected function tearDown(): void
    {
        unlink($this->file);

        parent::tearDown();
    }

    public function test_a_location_gets_the_coordinates_of_its_own_postcode_and_place(): void
    {
        $location = Location::create(['postcode' => '01561', 'city_name' => 'Ebersbach']);

        $this->artisan('app:import-location-coordinates', ['--file' => $this->file])->assertSuccessful();

        $this->assertEquals([51.2330, 13.6500], [$location->refresh()->latitude, $location->longitude]);
    }

    public function test_a_place_missing_from_the_file_gets_the_middle_of_its_postcode_area(): void
    {
        $location = Location::create(['postcode' => '01561', 'city_name' => 'Liega']);

        $this->artisan('app:import-location-coordinates', ['--file' => $this->file])->assertSuccessful();

        $this->assertEquals([51.2890, 13.6500], [$location->refresh()->latitude, $location->longitude]);
    }

    public function test_a_postcode_missing_from_the_file_falls_back_to_a_place_with_the_same_name(): void
    {
        $location = Location::create(['postcode' => '04861', 'city_name' => 'Torgau']);

        $this->artisan('app:import-location-coordinates', ['--file' => $this->file])->assertSuccessful();

        $this->assertEquals([51.5600, 13.0040], [$location->refresh()->latitude, $location->longitude]);
    }

    public function test_an_unknown_postcode_and_place_gets_the_middle_of_the_neighbouring_postcodes(): void
    {
        $location = Location::create(['postcode' => '04869', 'city_name' => 'Unbekannt']);

        $this->artisan('app:import-location-coordinates', ['--file' => $this->file])->assertSuccessful();

        $this->assertEquals([51.5600, 13.0040], [$location->refresh()->latitude, $location->longitude]);
    }

    public function test_a_location_that_cannot_be_matched_is_reported_and_left_without_coordinates(): void
    {
        $location = Location::create(['postcode' => '87567', 'city_name' => 'Riezlern']);

        $this->artisan('app:import-location-coordinates', ['--file' => $this->file])
            ->expectsOutputToContain('Without coordinates: 1')
            ->expectsOutputToContain('87567 Riezlern')
            ->assertSuccessful();

        $this->assertNull($location->refresh()->latitude);
    }

    public function test_a_missing_file_fails_without_touching_the_locations(): void
    {
        $this->artisan('app:import-location-coordinates', ['--file' => '/does/not/exist.tsv'])->assertFailed();
    }
}
