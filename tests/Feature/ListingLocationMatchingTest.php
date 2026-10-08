<?php

namespace Tests\Feature;

use App\Filament\Resources\ListingResource\Pages\ListListings;
use App\Mail\SearchAgentMatchesFound;
use App\Models\Listing;
use App\Models\Location;
use App\Models\SearchAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ListingLocationMatchingTest extends TestCase
{
    use RefreshDatabase;

    private Listing $inMitte;

    private Listing $nextDoor;

    private Listing $potsdam;

    private Listing $munich;

    protected function setUp(): void
    {
        parent::setUp();

        Location::create(['postcode' => '10115', 'city_name' => 'Berlin', 'latitude' => 52.5323, 'longitude' => 13.3846]);
        Location::create(['postcode' => '10117', 'city_name' => 'Berlin', 'latitude' => 52.5170, 'longitude' => 13.3872]);
        Location::create(['postcode' => '14467', 'city_name' => 'Potsdam', 'latitude' => 52.4009, 'longitude' => 13.0591]);
        Location::create(['postcode' => '80331', 'city_name' => 'München', 'latitude' => 48.1371, 'longitude' => 11.5754]);

        $this->inMitte = $this->createListing('Berlin Mitte', '10115');
        $this->nextDoor = $this->createListing('Berlin nebenan', '10117');
        $this->potsdam = $this->createListing('Potsdam', '14467');
        $this->munich = $this->createListing('München', '80331');
    }

    public function test_without_a_radius_only_the_postcode_itself_matches(): void
    {
        $this->assertEqualsCanonicalizing(
            [$this->inMitte->id],
            Listing::nearPostcode('10115')->pluck('id')->all(),
        );
    }

    public function test_a_radius_includes_listings_up_to_that_distance(): void
    {
        $this->assertEqualsCanonicalizing(
            [$this->inMitte->id, $this->nextDoor->id],
            Listing::nearPostcode('10115', 10)->pluck('id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$this->inMitte->id, $this->nextDoor->id, $this->potsdam->id],
            Listing::nearPostcode('10115', 50)->pluck('id')->all(),
        );
    }

    public function test_a_postcode_without_coordinates_matches_only_itself_even_with_a_radius(): void
    {
        $unknown = $this->createListing('Unbekannt', '99999');

        $this->assertSame([$unknown->id], Listing::nearPostcode('99999', 50)->pluck('id')->all());
    }

    public function test_the_admin_location_filter_shows_the_listings_within_the_radius(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListListings::class)
            ->assertCanSeeTableRecords([$this->inMitte, $this->nextDoor, $this->potsdam, $this->munich])
            ->set('tableFilters.location.postcode', '10115')
            ->assertCanSeeTableRecords([$this->inMitte])
            ->assertCanNotSeeTableRecords([$this->nextDoor, $this->potsdam, $this->munich])
            ->set('tableFilters.location.radius', 10)
            ->assertCanSeeTableRecords([$this->inMitte, $this->nextDoor])
            ->assertCanNotSeeTableRecords([$this->potsdam, $this->munich]);
    }

    public function test_the_email_run_only_sends_listings_within_the_search_agents_radius(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $agent = new SearchAgent(['user_id' => $user->id, 'title' => 'Berlin', 'postcode' => '10115', 'radius' => 10]);
        $agent->forceFill(['created_at' => now()->subDay()])->save();

        $this->artisan('app:run-search-agents')->assertSuccessful();

        Mail::assertSent(SearchAgentMatchesFound::class, 1);
        $this->assertEqualsCanonicalizing(
            [$this->inMitte->id, $this->nextDoor->id],
            $agent->listingMatches()->sole()->listing_ids_json,
        );
    }

    public function test_the_email_run_still_sends_everything_to_a_search_agent_without_a_location(): void
    {
        Mail::fake();

        $agent = new SearchAgent(['user_id' => User::factory()->create()->id, 'title' => 'Überall']);
        $agent->forceFill(['created_at' => now()->subDay()])->save();

        $this->artisan('app:run-search-agents')->assertSuccessful();

        $this->assertCount(4, $agent->listingMatches()->sole()->listing_ids_json);
    }

    private function createListing(string $title, string $zip): Listing
    {
        $listing = new Listing(['title' => $title, 'zip' => $zip]);
        $listing->forceFill(['hash' => hash('sha256', $title), 'price_cents' => 20_000_000])->save();

        return $listing;
    }
}
