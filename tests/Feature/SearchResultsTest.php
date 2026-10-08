<?php

namespace Tests\Feature;

use App\Mail\SearchAgentMatchesFound;
use App\Models\Listing;
use App\Models\SearchAgent;
use App\Models\SearchAgentListingMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SearchResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/suchergebnisse')->assertRedirect('/login');
    }

    public function test_only_emailed_matches_of_the_user_are_listed_newest_first(): void
    {
        $user = User::factory()->create();
        $agent = SearchAgent::create(['user_id' => $user->id, 'title' => 'Berlin Altbau']);

        $older = $this->createListing('Ältere Wohnung');
        $newer = $this->createListing('Neuere Wohnung');
        $unsent = $this->createListing('Nie gesendet');

        $this->createMatch($agent, [$older->id], '2026-09-19 13:29:00');
        $this->createMatch($agent, [$newer->id], '2026-09-26 19:54:00');
        $this->createMatch($agent, [$unsent->id], null);

        $otherAgent = SearchAgent::create(['user_id' => User::factory()->create()->id, 'title' => 'Fremd']);
        $foreign = $this->createListing('Fremdes Inserat');
        $this->createMatch($otherAgent, [$foreign->id], '2026-09-26 19:54:00');

        $this->actingAs($user)
            ->get('/suchergebnisse')
            ->assertOk()
            ->assertSeeInOrder([
                'Gesendet am 26.09.2026 um 21:54 Uhr',
                'Neuere Wohnung',
                'Gesendet am 19.09.2026 um 15:29 Uhr',
                'Ältere Wohnung',
            ])
            ->assertDontSee('Nie gesendet')
            ->assertDontSee('Fremdes Inserat');
    }

    public function test_running_search_agents_records_when_the_email_was_sent(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $agent = SearchAgent::create(['user_id' => $user->id, 'title' => 'Alles']);
        $this->createListing('Treffer');

        $this->artisan('app:run-search-agents')->assertSuccessful();

        Mail::assertSent(SearchAgentMatchesFound::class);
        $this->assertNotNull($agent->listingMatches()->sole()->emailed_at);
    }

    public function test_listings_can_be_saved_and_unsaved(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListing('Merkenswert');

        $this->actingAs($user);

        $component = Volt::test('listings.save-toggle', ['listingId' => $listing->id])
            ->call('toggle')
            ->assertSet('saved', true)
            ->assertSee('Gemerkt');

        $this->assertTrue($user->savedListings()->whereKey($listing->id)->exists());

        $component->call('toggle')->assertSet('saved', false)->assertSee('Merken');

        $this->assertFalse($user->savedListings()->exists());
    }

    public function test_merkliste_shows_only_the_users_saved_listings(): void
    {
        $user = User::factory()->create();
        $saved = $this->createListing('Gemerkte Wohnung');
        $this->createListing('Nicht gemerkt');
        $user->savedListings()->attach($saved->id);

        $this->actingAs($user)
            ->get('/suchergebnisse/merkliste')
            ->assertOk()
            ->assertSee('Merkliste')
            ->assertSee('Gemerkte Wohnung')
            ->assertDontSee('Nicht gemerkt');
    }

    public function test_saving_a_listing_announces_the_change_to_the_page(): void
    {
        $listing = $this->createListing('Merkenswert');

        $this->actingAs(User::factory()->create());

        Volt::test('listings.save-toggle', ['listingId' => $listing->id])
            ->call('toggle')
            ->assertDispatched('listing-saved-changed', listingId: $listing->id, saved: true);
    }

    public function test_merkliste_count_starts_with_the_number_of_saved_listings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('listings.saved-count')->assertSeeHtml('count: 0');

        $user->savedListings()->attach($this->createListing('Merkenswert')->id);

        Volt::test('listings.saved-count')->assertSeeHtml('count: 1');
    }

    public function test_a_save_button_can_pick_up_a_change_made_through_another_button(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListing('Merkenswert');

        $this->actingAs($user);

        $button = Volt::test('listings.save-toggle', ['listingId' => $listing->id])->assertSet('saved', false);

        $user->savedListings()->attach($listing->id);

        $button->call('refreshSaved')->assertSet('saved', true)->assertSee('Gemerkt');
    }

    public function test_merkliste_drops_a_listing_as_soon_as_it_is_unsaved(): void
    {
        $user = User::factory()->create();
        $kept = $this->createListing('Bleibt gemerkt');
        $removed = $this->createListing('Wird entfernt');
        $user->savedListings()->attach([$kept->id, $removed->id]);

        $this->actingAs($user);

        $component = Volt::test('listings.saved-list')
            ->assertSee('Bleibt gemerkt')
            ->assertSee('Wird entfernt');

        $user->savedListings()->detach($removed->id);

        $component
            ->dispatch('listing-saved-changed', listingId: $removed->id, saved: false)
            ->assertSee('Bleibt gemerkt')
            ->assertDontSee('Wird entfernt');
    }

    public function test_merkliste_falls_back_to_the_previous_page_when_its_last_page_empties(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 21) as $number) {
            $user->savedListings()->attach(
                $this->createListing("Wohnung {$number}")->id,
                ['created_at' => now()->subMinutes($number)],
            );
        }

        $oldest = $user->savedListings()->orderByPivot('created_at')->first();

        $this->actingAs($user);

        $component = Volt::test('listings.saved-list')
            ->call('setPage', 2)
            ->assertSee('Wohnung 21')
            ->assertDontSee('Wohnung 20');

        $user->savedListings()->detach($oldest->id);

        $component
            ->dispatch('listing-saved-changed', listingId: $oldest->id, saved: false)
            ->assertSee('Wohnung 20')
            ->assertDontSee('Deine Merkliste ist noch leer');
    }

    public function test_search_results_mark_already_saved_listings(): void
    {
        $user = User::factory()->create();
        $agent = SearchAgent::create(['user_id' => $user->id, 'title' => 'Berlin']);
        $listing = $this->createListing('Schon gemerkt');
        $this->createMatch($agent, [$listing->id], '2026-09-26 19:54:00');
        $user->savedListings()->attach($listing->id);

        $this->actingAs($user)
            ->get('/suchergebnisse')
            ->assertOk()
            ->assertSee('Gemerkt')
            ->assertSee(route('search-results.saved'));
    }

    private function createListing(string $title): Listing
    {
        $listing = new Listing(['title' => $title]);
        $listing->forceFill(['hash' => hash('sha256', $title), 'price_cents' => 10_000_000])->save();

        return $listing;
    }

    private function createMatch(SearchAgent $agent, array $listingIds, ?string $emailedAt): void
    {
        SearchAgentListingMatch::create([
            'search_agent_id' => $agent->id,
            'user_id' => $agent->user_id,
            'listing_ids_json' => $listingIds,
            'match_count' => count($listingIds),
            'matched_at' => $emailedAt ?? now(),
            'emailed_at' => $emailedAt,
        ]);
    }
}
