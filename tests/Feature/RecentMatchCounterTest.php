<?php

namespace Tests\Feature;

use App\Livewire\SearchAgents\Manager;
use App\Models\Listing;
use App\Models\Location;
use App\Models\SearchAgent;
use App\Models\User;
use App\Support\SearchAgents\RecentMatchCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecentMatchCounterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_only_listings_added_in_the_last_seven_days_are_counted(): void
    {
        $this->createListing(['created_at' => now()->subDays(6)]);
        $this->createListing(['created_at' => now()->subDays(8)]);

        $this->assertSame(1, $this->countFor([]));
    }

    public function test_listings_outside_the_price_range_are_not_counted(): void
    {
        $this->createListing(['price_cents' => 15_000_000]);
        $this->createListing(['price_cents' => 9_000_000]);
        $this->createListing(['price_cents' => 31_000_000]);
        $this->createListing(['price_cents' => null]);

        $this->assertSame(1, $this->countFor(['price_from' => 100000, 'price_to' => 300000]));
    }

    public function test_listings_outside_the_potential_return_range_are_not_counted(): void
    {
        $this->createListing(['rendite_pot_num' => 4.5]);
        $this->createListing(['rendite_pot_num' => 2.1]);
        $this->createListing(['rendite_pot_num' => 9.0]);
        $this->createListing(['rendite_pot_num' => null]);

        $this->assertSame(1, $this->countFor(['pot_return_from' => 4, 'pot_return_to' => 6]));
    }

    public function test_listings_outside_the_size_range_are_not_counted(): void
    {
        $this->createListing(['flaeche' => '60m²']);
        $this->createListing(['flaeche' => '35m²']);
        $this->createListing(['flaeche' => '1.200m²']);
        $this->createListing(['flaeche' => null]);

        $this->assertSame(1, $this->countFor(['size_from' => 50, 'size_to' => 100]));
    }

    public function test_listings_outside_the_room_range_are_not_counted(): void
    {
        $this->createListing(['zimmer' => '3']);
        $this->createListing(['zimmer' => '2,5']);
        $this->createListing(['zimmer' => '1']);
        $this->createListing(['zimmer' => '-']);

        $this->assertSame(2, $this->countFor(['min_rooms' => 2, 'max_rooms' => 4]));
    }

    public function test_a_postcode_without_a_radius_counts_only_listings_in_that_postcode(): void
    {
        $this->createListing(['zip' => '10115']);
        $this->createListing(['zip' => '10117']);

        $this->assertSame(1, $this->countFor(['postcode' => '10115', 'radius' => 0]));
    }

    public function test_a_postcode_with_a_radius_counts_listings_within_that_distance(): void
    {
        Location::create(['postcode' => '10115', 'city_name' => 'Berlin', 'latitude' => 52.5323, 'longitude' => 13.3846]);
        Location::create(['postcode' => '10117', 'city_name' => 'Berlin', 'latitude' => 52.5170, 'longitude' => 13.3872]);
        Location::create(['postcode' => '80331', 'city_name' => 'München', 'latitude' => 48.1371, 'longitude' => 11.5754]);

        $this->createListing(['zip' => '10115']);
        $this->createListing(['zip' => '10117']);
        $this->createListing(['zip' => '80331']);

        $this->assertSame(2, $this->countFor(['postcode' => '10115', 'radius' => 10]));
    }

    public function test_updating_a_search_agent_reports_how_many_recent_listings_fit(): void
    {
        $this->createListing(['price_cents' => 15_000_000]);
        $this->createListing(['price_cents' => 20_000_000]);
        $this->createListing(['price_cents' => 90_000_000]);
        $agent = $this->createAgent([]);

        $this->actingAs($this->user);

        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('editingId', $agent->id)
            ->set('title', 'Berlin')
            ->set('price_to', '300000')
            ->call('save')
            ->assertDispatched('search-agent-updated', details: 'In den letzten 7 Tagen passten 2 Inserate zu diesen Suchkriterien.');
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function countFor(array $criteria): int
    {
        return (new RecentMatchCounter)->count($this->createAgent($criteria));
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function createAgent(array $criteria): SearchAgent
    {
        return SearchAgent::create(['user_id' => $this->user->id, 'title' => 'Berlin', ...$criteria])->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createListing(array $attributes): void
    {
        (new Listing)->forceFill([
            'hash' => hash('sha256', uniqid('', true)),
            'price_cents' => 20_000_000,
            'created_at' => now()->subDay(),
            ...$attributes,
        ])->save();
    }
}
